<?php
declare(strict_types=1);

namespace Marketplacer\Base\Model\ModuleInfo;

use Magento\Framework\Config\Data;

class ModuleInfoConfig extends Data
{
    /**
     * Retrieve the module version
     *
     * @param string $moduleName
     * @return ?string
     */
    public function getVersion(string $moduleName): ?string
    {
        return $this->get($moduleName. '/version');
    }
}
