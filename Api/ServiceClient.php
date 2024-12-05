<?php
declare(strict_types=1);

namespace Marketplacer\Base\Api;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Marketplacer\Base\Model\Config;
use Psr\Log\LoggerInterface;

class ServiceClient implements ServiceClientInterface
{
    /**
     * Extension name for Services Connector
     */
    private const EXTENSION_NAME = 'Marketplacer_Marketplacer';

    /**
     * @param ClientResolverInterface $clientResolver
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ClientResolverInterface $clientResolver,
        private readonly Json $serializer,
        private readonly LoggerInterface $logger,
        private readonly Config $baseConfig
    ) {
    }

    /**
     * Execute call
     *
     * @param array $headers
     * @param string $path
     * @param string $data
     * @return array
     * @throws LocalizedException
     */
    public function request(array $headers, string $path, string $data = ''): array
    {
        try {
            $client = $this->clientResolver->createHttpClient();

            $headers = \array_merge(
                $headers,
                [
                    'Content-Type' => 'application/json',
                    'marketplacer-api-key' => $this->baseConfig->getToken()
                ]
            );

            $options = [
                'headers' => $headers,
                'body' => $data
            ];
            $response = $client->request('POST', $path, $options);

            $result = $this->serializer->unserialize($response->getBody()->getContents());
            $result['status'] = $response->getStatusCode();

            if ($response->getStatusCode() >= 400 || isset($result['errors'])) {
                $this->logger->error(
                    self::EXTENSION_NAME . ': An error occurred in search backend.',
                    ['result' => $result, 'request_id' => $response->getHeader('X-Request-Id'), 'status_code' => $response->getStatusCode()]
                );

                throw new LocalizedException(__($result['errors'][0]['message'] ?? 'An error occurred in search backend.'));
            }
            return $result;
        } catch (GuzzleException $e) {
            $this->logger->error(
                self::EXTENSION_NAME . ': An error occurred trying to contact search backend: ' . $e->getMessage()
            );
            throw new LocalizedException(__('An error occurred trying to contact search backend.'));
        }
    }
}
