<?php

namespace Marketplacer\Base\Test\Unit\Model\Attribute;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributeRetrieverTest extends TestCase
{
    use \Marketplacer\Base\Test\Unit\Traits\ReflectionTrait;

    /**
     * @var ProductAttributeRepositoryInterface|MockObject
     */
    private $productAttributeRepositoryMock;

    /**
     * @var AttributeInterface|MockObject
     */
    private $attributeMock;

    /**
     * @var string
     */
    private $attributeCode = 'test_attribute';

    /**
     * @var \Marketplacer\Base\Model\Attribute\AttributeRetriever
     */
    private $attributeRetriever;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->productAttributeRepositoryMock = $this->createMock(\Magento\Catalog\Model\Product\Attribute\Repository::class);
        $this->attributeMock = $this->createPartialMock(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class, ['getAttributeCode']);

        $this->attributeRetriever = $this->objectManager->getObject(
            \Marketplacer\Base\Model\Attribute\AttributeRetriever::class,
            [
                'productAttributeRepository' => $this->productAttributeRepositoryMock,
                'attributeCode'    => $this->attributeCode,
            ]
        );
    }

    public function testGetAttributeNotCached()
    {
        $this->productAttributeRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->attributeMock);

        $this->assertEquals($this->attributeMock, $this->attributeRetriever->getAttribute());
    }

    public function testGetAttributeCached() {
        $this->setProperty($this->attributeRetriever, 'attribute', $this->attributeMock);

        $this->productAttributeRepositoryMock
            ->expects($this->never())
            ->method('get');

        $this->assertEquals($this->attributeMock, $this->attributeRetriever->getAttribute());
    }

    public function testGetAttributeCode()
    {
        $this->productAttributeRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->attributeMock);

        $this->attributeMock
            ->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn($this->attributeCode);

        $this->assertEquals($this->attributeCode, $this->attributeRetriever->getAttributeCode());
    }

    public function testConstructorWithoutAttributeCode()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->attributeRetriever = $this->objectManager->getObject(
            \Marketplacer\Base\Model\Attribute\AttributeRetriever::class,
            [
                'productAttributeRepository' => $this->productAttributeRepositoryMock,
            ]
        );
    }
}
