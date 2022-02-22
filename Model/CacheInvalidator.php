<?php

namespace Marketplacer\Base\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Marketplacer\Base\Api\CacheInvalidatorInterface;

class CacheInvalidator implements CacheInvalidatorInterface
{
    /**
     * @var TypeListInterface
     */
    protected $typeList;

    /**
     * @var array
     */
    protected $defaultTypesToInvalidate = [];

    /**
     * CacheInvalidator constructor.
     * @param TypeListInterface $typeList
     * @param array $defaultTypesToInvalidate
     */
    public function __construct(TypeListInterface $typeList, array $defaultTypesToInvalidate = [])
    {
        $this->typeList = $typeList;
        $this->defaultTypesToInvalidate = $defaultTypesToInvalidate;
    }

    /**
     * @param array $typeList
     * @param bool $forceSpecified
     * @return void
     */
    public function invalidate(array $typeList = [], $forceSpecified = false)
    {
        if (!$forceSpecified) {
            $typeList = array_merge($this->defaultTypesToInvalidate, $typeList);
        }
        $this->typeList->invalidate($typeList);
    }
}
