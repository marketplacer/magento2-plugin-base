<?php

namespace Marketplacer\Base\Model\Attribute;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Model\Store;
use Marketplacer\Base\Model\ResourceModel\Attribute\AttributeOption;

class AttributeOptionHandler
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $productAttributeRepository;

    /**
     * @var ProductAttributeOptionManagementInterfaceFactory
     */
    protected $productAttributeOptionManagementFactory;

    /**
     * @var AttributeOption
     */
    protected $attributeOptionResource;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    protected $attributeOptionFactory;

    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    protected $attributeOptionLabelFactory;

    /**
     * @var AttributeOptionInterface[][]
     */
    protected $optionsCache;

    /**
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param ProductAttributeOptionManagementInterfaceFactory $productAttributeOptionManagementFactory
     * @param AttributeOption $attributeOptionResource
     * @param AttributeOptionInterfaceFactory $attributeOptionFactory
     * @param AttributeOptionLabelInterfaceFactory $attributeOptionLabelFactory
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ProductAttributeOptionManagementInterfaceFactory $productAttributeOptionManagementFactory,
        AttributeOption $attributeOptionResource,
        AttributeOptionInterfaceFactory $attributeOptionFactory,
        AttributeOptionLabelInterfaceFactory $attributeOptionLabelFactory
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeOptionManagementFactory = $productAttributeOptionManagementFactory;
        $this->attributeOptionResource = $attributeOptionResource;
        $this->attributeOptionFactory = $attributeOptionFactory;
        $this->attributeOptionLabelFactory = $attributeOptionLabelFactory;
    }

    /**
     * @param ProductAttributeInterface $attribute
     * @param int | string | null $productOptionId
     * @return bool
     * @throws LocalizedException
     */
    public function isAttributeOptionIdExist(ProductAttributeInterface $attribute, $productOptionId)
    {
        if ($productOptionId) {
            return (bool)$this->getAttributeOptionById($attribute, $productOptionId);
        }

        return false;
    }

    /**
     * @param int $optionId
     * @param bool $forceReload
     * @return AttributeOptionInterface
     * @throws LocalizedException
     */
    public function getAttributeOptionById(ProductAttributeInterface $attribute, $optionId, $forceReload = false)
    {
        $attributeCode = $attribute->getAttributeCode();

        if (!isset($this->optionsCache[$attributeCode][$optionId]) || $forceReload) {
            $found = false;
            /** @see \Magento\Eav\Model\Entity\Attribute\Source\Table::getAllOptions */
            $attributeOptions = $attribute->getOptions();
            foreach ($attributeOptions as $attributeOption) {
                if ($attributeOption->getValue() == $optionId) {
                    $optionsWithLabels = $this->getAttributeOptionsWithStoreLabels($attribute, [$optionId]);
                    $this->optionsCache[$attributeCode][$optionId] = $optionsWithLabels[$optionId] ?? null;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->optionsCache[$attributeCode][$optionId] = null;
            }
        }
        return $this->optionsCache[$attributeCode][$optionId];
    }

    /**
     * @param ProductAttributeInterface $attribute
     * @param array $optionIds
     * @return AttributeOptionInterface[]
     * @throws LocalizedException
     */
    protected function getAttributeOptionsWithStoreLabels(ProductAttributeInterface $attribute, array $optionIds = [])
    {
        $connection = $this->attributeOptionResource->getConnection();
        $select = $connection->select()
            ->from(['eav_ao' => $this->attributeOptionResource->getMainTable()])
            ->joinLeft(
                ['eav_aov' => $this->attributeOptionResource->getTable('eav_attribute_option_value')],
                'eav_ao.option_id = eav_aov.option_id'
            )
            ->where('eav_ao.attribute_id = ?', $attribute->getAttributeId());

        $optionIds = array_unique(array_filter($optionIds));
        if ($optionIds) {
            $select->where('eav_ao.option_id IN (?)', $optionIds);
        }

        $optionItems = $connection->fetchAll($select);
        $indexedOptionsData = [];
        foreach ($optionItems as $item) {
            $optionId = $item['option_id'];

            if (Store::DEFAULT_STORE_ID == $item['store_id']) {
                $indexedOptionsData[$optionId]['default_label'] = $item['value'];
            } else {
                $storeLabel = $this->attributeOptionLabelFactory->create();
                $storeLabel->setData([
                    AttributeOptionLabelInterface::STORE_ID => $item['store_id'],
                    AttributeOptionLabelInterface::LABEL    => $item['value'],
                ]);

                $indexedOptionsData[$optionId]['labels'][] = $storeLabel;
            }

            if (!isset($indexedOptionsData[$optionId]['sort_order'])) {
                $indexedOptionsData[$optionId]['sort_order'] = $item['sort_order'] ?? null;
            }
        }

        $indexedOptions = [];
        foreach ($attribute->getOptions() as $option) {
            $optionId = $option->getValue();
            if (in_array($optionId, $optionIds)) {
                //unbind object to avoid current store labels affecting
                $option = clone $option;

                $option->setLabel($indexedOptionsData[$optionId]['default_label']);

                if (!is_array($option->getStoreLabels()) && isset($indexedOptionsData[$optionId]['labels'])) {
                    $option->setStoreLabels($indexedOptionsData[$optionId]['labels'] ?? []);
                }

                if (!$option->hasSortOrder() && isset($indexedOptionsData[$optionId]['sort_order'])) {
                    $option->setSortOrder($indexedOptionsData[$optionId]['sort_order']);
                }

                $indexedOptions[$optionId] = $option;
            }
        }

        return $indexedOptions;
    }

    /**
     * @param ProductAttributeInterface $attribute
     * @param AttributeOptionInterface $productOption
     * @return AttributeOptionInterface
     * @throws InputException
     * @throws StateException
     */
    public function saveAttributeOption(ProductAttributeInterface $attribute, AttributeOptionInterface $productOption)
    {
        $productAttributeOptionManagement = $this->productAttributeOptionManagementFactory->create();

        if ($productOption->getValue()) {
            $productAttributeOptionManagement->update(
                $attribute->getAttributeCode(),
                $productOption->getValue(),
                $productOption
            );
        } else {
            $productAttributeOptionManagement->add(
                $attribute->getAttributeCode(),
                $productOption
            );
        }

        return $productOption;
    }

    /**
     * @return AttributeOptionInterface
     */
    public function createAttributeOption()
    {
        $attributeOption = $this->attributeOptionFactory->create();
        $attributeOption->setIsDefault(0);
        $attributeOption->setSortOrder(0);
        $attributeOption->setValue(null);

        return $attributeOption;
    }

    /**
     * @param ProductAttributeInterface $attribute
     * @param array $optionIds
     * @param bool $forceReload
     * @return AttributeOptionInterface[]
     * @throws LocalizedException
     */
    public function getAttributeOptionsByIds(
        ProductAttributeInterface $attribute,
        array $optionIds,
        $forceReload = false
    ) {
        $attributeCode = $attribute->getAttributeCode();

        if (!$optionIds) {
            return [];
        }

        $missingOptionIds = array_diff(
            array_values($optionIds),
            array_keys($this->optionsCache[$attribute->getAttributeCode()] ?? [])
        );

        if ($missingOptionIds) {
            if (!isset($this->optionsCache[$attributeCode])) {
                $this->optionsCache[$attributeCode] = [];
            }

            //preload missing options
            $optionsWithLabels = $this->getAttributeOptionsWithStoreLabels($attribute, $missingOptionIds);
            if ($optionsWithLabels) {
                //merge cached options preserving keys
                $this->optionsCache[$attributeCode] = $this->optionsCache[$attributeCode] + $optionsWithLabels;
            }
        }

        $indexedOptions = [];
        foreach ($optionIds as $optionId) {
            $indexedOptions[$optionId] = $this->getAttributeOptionById($attribute, $optionId, $forceReload);
        }

        return $indexedOptions;
    }

    /**
     * Check is Admin Label unique
     * @param ProductAttributeInterface $attribute
     * @param AttributeOptionInterface $option
     * @return bool
     * @throws LocalizedException
     */
    public function isAdminLabelUnique(ProductAttributeInterface $attribute, AttributeOptionInterface $option)
    {
        return $this->attributeOptionResource->isAdminLabelUnique($attribute, $option);
    }

    /**
     * @param int | string $optionId
     * @return bool
     * @throws NoSuchEntityException
     * @throws InputException
     * @throws StateException
     */
    public function deleteOptionById(ProductAttributeInterface $attribute, $optionId)
    {
        $optionManagement = $this->productAttributeOptionManagementFactory->create();
        $deleteResult = $optionManagement->delete($attribute->getAttributeCode(), $optionId);

        unset($this->optionsCache[$attribute->getAttributeCode()][$optionId]);

        return $deleteResult;
    }
}
