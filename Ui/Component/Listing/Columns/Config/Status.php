<?php

namespace Marketplacer\Base\Ui\Component\Listing\Columns\Config;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 1,
                'label' => __('Enabled')
            ],
            [
                'value' => 0,
                'label' => __('Disabled')
            ]
        ];
    }
}
