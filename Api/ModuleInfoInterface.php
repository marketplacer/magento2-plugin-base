<?php
declare(strict_types=1);

namespace Marketplacer\Base\Api;

interface ModuleInfoInterface
{
    /**
     * Retrieving a list of modules
     *
     * @return mixed
     */
    public function getList(): array;
}
