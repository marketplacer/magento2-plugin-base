<?php

namespace Marketplacer\Base\Model\Attribute;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AttributeRetriever implements \Marketplacer\Base\Api\AttributeRetrieverInterface
{
    /**
     * @var string
     */
    protected $attributeCode;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $productAttributeRepository;

    /**
     * @var AttributeInterface
     */
    protected $attribute;

    /**
     * AttributeRetriever constructor.
     *
     * You need to pass the corresponding attribute code to use it
     *
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param string | null $attributeCode
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ?string $attributeCode
    ) {
        if (!$attributeCode) {
            throw new InvalidArgumentException(
                (string)__('You need to pass attribute code in %1 class using DI to use it.', self::class)
            );
        }
        $this->attributeCode = $attributeCode;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAttributeCode()
    {
        return $this->getAttribute()->getAttributeCode();
    }

    /**
     * @return ProductAttributeInterface
     * @throws NoSuchEntityException
     */
    public function getAttribute()
    {
        if ($this->attribute === null) {
            try {
                $this->attribute = $this->productAttributeRepository->get($this->attributeCode);
            } catch (NoSuchEntityException $exception) {
                throw new NoSuchEntityException(__('Attribute "%1" is not found', $this->attributeCode));
            }
        }

        return $this->attribute;
    }
}
