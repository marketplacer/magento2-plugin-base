<?php
declare(strict_types=1);

namespace Marketplacer\Base\Model\ModuleInfo\Config;

use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{
    /**
     * Convert config
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source): array
    {
        $output = [];

        /** @var \DOMElement $module */
        foreach ($source->getElementsByTagName('module') as $module) {
            $name = $module->getAttribute('name');
            $output[$name] = [
                'version' => $module->getElementsByTagName('version')->item(0)->textContent
            ];
        }

        return $output;
    }
}
