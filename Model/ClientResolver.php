<?php
declare(strict_types=1);

namespace Marketplacer\Base\Model;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Magento\Framework\App\ProductMetadataInterface;
use Marketplacer\Base\Api\ClientResolverInterface;
use Psr\Http\Message\RequestInterface;

class ClientResolver implements ClientResolverInterface
{
    private ?HandlerStack $stack = null;

    /**
     *
     * @param GuzzleClientFactory $clientFactory
     * @param ProductMetadataInterface $productMetadata
     * @param Config $baseConfig
     */
    public function __construct(
        private readonly GuzzleClientFactory $clientFactory,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly Config $baseConfig
    ) {
    }

    /**
     * Provides a configured HTTP client
     *
     * The client also adds authentication headers(api keys) to every applicable HTTP request
     *
     * @param string $hostname if provided allows to override the base URL
     * @param callable[] $middlewares List of middlewares that allows the user to decorate the client for its own
     * needs. Middleware should be a callable function with an specific format:
     * @see https://docs.guzzlephp.org/en/latest/handlers-and-middleware.html#middleware
     * @throws InvalidArgumentException
     * @return Client
     */
    public function createHttpClient(
        string $hostname = '',
        $middlewares = []
    ): Client {
        if ($accessToken = $this->baseConfig->getToken()) {
            $middlewares[] = Middleware::mapRequest(function (RequestInterface $request) use ($accessToken) {
                $header = '';
                $credentials = base64_encode($this->baseConfig->getHttpLogin() .':' . $this->baseConfig->getHttpPassword());
                $basicAuth = sprintf('Basic %s', $credentials);

                if ($this->baseConfig->getHttpLogin() && $this->baseConfig->getHttpPassword() && $accessToken) {
                    $header = $basicAuth;
                }

                if (!$this->baseConfig->getHttpLogin() || !$this->baseConfig->getHttpPassword() && $accessToken) {
                    $header = 'Bearer ' . $accessToken;
                }

                return $request
                    ->withHeader('Authorization', $header);
            });
        }

        $this->addMiddlewaresToStack($middlewares);

        $authenticationData = [$this->baseConfig->getHttpLogin(), $this->baseConfig->getHttpPassword()];

        if (!$this->baseConfig->getHttpLogin() || !$this->baseConfig->getHttpPassword()) {
            $authenticationData[2] = null;
        }

        $headerData = [
            RequestOptions::HTTP_ERRORS => false,
            'base_uri' => $hostname,
            'handler' => $this->getStack(),
            'auth' => $authenticationData,
            'headers' => ['User-Agent' => $this->getUserAgent()],
        ];

        if ($this->baseConfig->getHttpLogin() && $this->baseConfig->getHttpPassword()) {
            $headerData['marketplacer-api-key'] = $this->baseConfig->getToken();
        }

        return $this->clientFactory->create(
            $headerData
        );
    }

    /**
     * Gets user agent info
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        return sprintf(
            'Magento Services Connector (Magento: %s)',
            $this->productMetadata->getEdition() . ' '
            . $this->productMetadata->getVersion()
        );
    }

    /**
     * Adds the list of middlewares to the handler stack.
     *
     * @see https://docs.guzzlephp.org/en/latest/handlers-and-middleware.html#middleware
     *
     * @param callable[] $middlewares
     * @return void
     */
    private function addMiddlewaresToStack(array $middlewares): void
    {
        if (!empty($middlewares)) {
            foreach ($middlewares as $middleware) {
                if (is_callable($middleware)) {
                    $this->getStack()->push($middleware);
                }
            }
        }
    }

    /**
     * Stack getter
     *
     * @return HandlerStack
     */
    private function getStack(): HandlerStack
    {
        if (!isset($this->stack)) {
            $this->stack = HandlerStack::create();
        }
        return $this->stack;
    }
}
