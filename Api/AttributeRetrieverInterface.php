<?php

namespace Marketplacer\Base\Api;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface AttributeRetrieverInterface
{
    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAttributeCode();

    /**
     * @return ProductAttributeInterface
     * @throws NoSuchEntityException
     */
    public function getAttribute();
}
