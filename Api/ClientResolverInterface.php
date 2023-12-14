<?php
declare(strict_types=1);

namespace Marketplacer\Base\Api;

use Psr\Http\Client\ClientExceptionInterface;

interface ClientResolverInterface
{
    /**
     * Provides a configured HTTP client
     *
     * The client also adds authentication headers(api keys) to every applicable HTTP request
     *
     * @param string $hostname if provided allows to override the base URL
     * @param callable[] $middlewares List of middlewares that allows the user to decorate the client for its own
     * needs. Middleware should be a callable function with an specific format:
     * @see https://docs.guzzlephp.org/en/latest/handlers-and-middleware.html#middleware
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Client
     */
    public function createHttpClient(
        string $hostname = '',
        array $middlewares = []
    ): \GuzzleHttp\Client;
}
