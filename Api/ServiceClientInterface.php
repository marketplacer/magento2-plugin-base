<?php
declare(strict_types=1);

namespace Marketplacer\Base\Api;

interface ServiceClientInterface
{
    /**
     * Execute call
     *
     * @param array $headers
     * @param string $path
     * @param string $data
     * @return array
     */
    public function request(array $headers, string $path, string $data = ''): array;
}
