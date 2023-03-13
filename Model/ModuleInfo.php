<?php
declare(strict_types=1);

namespace Marketplacer\Base\Model;

use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\ModuleList;
use Marketplacer\Base\Api\ModuleInfoInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Serialize\Serializer\Json;
use Marketplacer\Base\Model\ModuleInfo\ModuleInfoConfig;
use \Throwable;

class ModuleInfo implements ModuleInfoInterface
{
    /**
     * @param ModuleList $moduleList
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     * @param Json $json
     * @param ModuleInfoConfig $moduleInfoConfig
     */
    public function __construct(
        private ModuleList $moduleList,
        private ComponentRegistrarInterface $componentRegistrar,
        private ReadFactory $readFactory,
        private Json $json,
        private ModuleInfoConfig $moduleInfoConfig
    ) {
    }

    /**
     * Retrieving a list of modules
     *
     * @return array
     */
    public function getList(): array
    {
        $listModules = $this->moduleList->getNames();
        $results = [];
        foreach ($listModules as $name) {
            $moduleInfo = $this->getModuleInfo($name);
            $moduleInfo['name'] = $name;
            $results[] = $moduleInfo;

        }
        return $results;
    }

    /**
     * Get info about the module
     *
     * @param string $moduleName
     * @return array
     */
    private function getModuleInfo(string $moduleName): array
    {
        $path = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            $moduleName
        );
        $directoryRead = $this->readFactory->create($path);
        try {
            $composerJsonData = $directoryRead->readFile('composer.json');
            $data = $this->json->unserialize($composerJsonData);
        } catch (Throwable $exception) {
            return [];
        }

        return [
            'version' => !empty($data['version']) ? $data['version'] : $this->moduleInfoConfig->getVersion($moduleName)
        ];
    }
}
