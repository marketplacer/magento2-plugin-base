<?php

namespace Marketplacer\Base\ViewModel;

use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class BaseViewModel implements ArgumentInterface
{
    /**
     * Escaper. Temporary added for Magento < 2.4
     *
     * @var Escaper
     */
    protected $escaper;

    /**
     * BaseViewModel constructor.
     * @param Escaper $escaper
     * @param array $data
     */
    public function __construct(
        Escaper $escaper,
        array $data = []
    ) {
        $this->escaper = $escaper;
    }

    /**
     * Temporary wrapper for Magento < 2.4
     *
     * @return Escaper
     */
    public function getEscaper()
    {
        return $this->escaper;
    }
}
