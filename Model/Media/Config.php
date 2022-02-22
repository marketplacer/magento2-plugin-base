<?php

namespace Marketplacer\Base\Model\Media;

use Magento\Framework\UrlInterface;

/**
 * Class Config
 * @package Magento\Catalog\Model\Product\Media
 */
class Config extends \Magento\Catalog\Model\Product\Media\Config
{
    const VALID_TYPES = ['gif', 'jpeg', 'jpg', 'png'];

    const MEDIA_URL = 'marketplacer/entity/images';
    const MEDIA_PATH = 'marketplacer/entity/images';

    /**
     * {@inheritdoc}
     */
    public function getBaseMediaPathAddition()
    {
        return self::MEDIA_PATH;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseMediaUrlAddition()
    {
        return self::MEDIA_URL;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseMediaPath()
    {
        return self::MEDIA_PATH;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseMediaUrl()
    {
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . self::MEDIA_URL;
    }
}
