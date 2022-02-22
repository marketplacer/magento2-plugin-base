<?php

namespace Marketplacer\Base\Api;

interface CacheInvalidatorInterface
{
    /**
     * @param array $typeList
     * @param bool $forceSpecified
     * @return void
     */
    public function invalidate(array $typeList = [], $forceSpecified = false);
}
