<?php
declare(strict_types=1);

namespace Marketplacer\Base\Model;

use GuzzleHttp\Client;
use Magento\Framework\ObjectManagerInterface;

class GuzzleClientFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return Client
     */
    public function create(array $data = []): Client
    {
        return $this->objectManager->create(Client::class, ['config' => $data]);
    }
}
