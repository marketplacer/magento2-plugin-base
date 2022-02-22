<?php

namespace Marketplacer\Base\Model\ResourceModel\Attribute;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\Store;
use Zend_Db_Expr;

/**
 * Class AttributeOption
 * @package Marketplacer\Base\Model\ResourceModel\Attribute
 */
class AttributeOption extends AbstractDb
{
    /**
     * Check if Admin Label of Attribute option is unique
     *
     * @param ProductAttributeInterface $attribute
     * @param AttributeOptionInterface $option
     * @return bool
     * @throws LocalizedException
     */
    public function isAdminLabelUnique(ProductAttributeInterface $attribute, AttributeOptionInterface $option)
    {
        $conn = $this->getConnection();
        $select = $conn->select()
            ->from(['options' => $this->getMainTable()])
            ->join(
                ['values' => $this->getTable('eav_attribute_option_value')],
                new Zend_Db_Expr('options.option_id = values.option_id')
            )
            ->where('options.attribute_id = ?', $attribute->getAttributeId())
            ->where('values.store_id = ?', Store::DEFAULT_STORE_ID)
            ->where('values.value = ?', $option->getLabel())
            ->limit(1);

        if ($option->getValue()) {
            $select->where('options.option_id != ?', $option->getValue());
        }

        return $conn->fetchOne($select) == false;
    }

    /**
     * Resource initialization
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init('eav_attribute_option', 'option_id');
    }
}
