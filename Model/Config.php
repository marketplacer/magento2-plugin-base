<?php
declare(strict_types=1);

namespace Marketplacer\Base\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public const XML_PATH_MARKETPLACER_API_ENDPOINT = 'marketplacer_base/base/api_endpoint';
    public const XML_PATH_MARKETPLACER_API_KEY = 'marketplacer_base/base/api_key';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Retrieve the api endpoint
     *
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_MARKETPLACER_API_ENDPOINT
        );
    }

    /**
     * Retrieve the api token
     *
     * @return string
     */
    public function getToken(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_MARKETPLACER_API_KEY
        );
    }
}
