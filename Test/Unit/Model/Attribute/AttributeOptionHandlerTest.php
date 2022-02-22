<?php

namespace Marketplacer\Base\Test\Unit\Model\Attribute;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributeOptionHandlerTest extends TestCase
{
    use \Marketplacer\Base\Test\Unit\Traits\ReflectionTrait;

    /**
     * @var ProductAttributeRepositoryInterface|MockObject
     */
    private $productAttributeRepositoryMock;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeOptionManagementInterfaceFactory|MockObject
     */
    private $productAttributeOptionManagementFactoryMock;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeOptionManagementInterface|MockObject
     */
    private $productAttributeOptionManagementMock;

    /**
     * @var \Marketplacer\Base\Model\ResourceModel\Attribute\AttributeOption|MockObject
     */
    private $attributeOptionResourceMock;

    /**
     * @var AttributeOptionInterfaceFactory|MockObject
     */
    private $attributeOptionFactoryMock;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory|MockObject
     */
    private $attributeOptionLabelFactoryMock;

    /**
     * @var AttributeInterface|MockObject
     */
    private $attributeMock;

    /**
     * @var string
     */
    private $attributeCode = 'test_attribute';

    /**
     * @var \Marketplacer\Base\Model\Attribute\AttributeOptionHandler
     */
    private object $attributeOptionHandler;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|MockObject
     */
    private $connectionMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->productAttributeRepositoryMock = $this->createMock(\Magento\Catalog\Model\Product\Attribute\Repository::class);

        $this->productAttributeOptionManagementMock = $this->createMock(\Magento\Catalog\Model\Product\Attribute\OptionManagement::class);
        $this->productAttributeOptionManagementFactoryMock = $this->createMock(\Magento\Catalog\Api\ProductAttributeOptionManagementInterfaceFactory::class);
        $this->productAttributeOptionManagementFactoryMock->method('create')->willReturn($this->productAttributeOptionManagementMock);

        $this->attributeOptionFactoryMock = $this->createMock(\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class);

        $this->attributeOptionLabelFactoryMock = $this->createMock(\Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory::class);
        $this->attributeOptionLabelFactoryMock->method('create')->willReturn($this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\OptionLabel::class));

        $this->attributeOptionResourceMock = $this->createMock(\Marketplacer\Base\Model\ResourceModel\Attribute\AttributeOption::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->connectionMock->method('select')->willReturn($selectMock);
        $selectMock->method('from')->willReturn($selectMock);
        $selectMock->method('joinLeft')->willReturn($selectMock);
        $selectMock->method('where')->willReturn($selectMock);
        $this->attributeOptionResourceMock->method('getConnection')->willReturn($this->connectionMock);
        $this->attributeOptionResourceMock->method('getMainTable')->willReturn('no matter');
        $this->attributeOptionResourceMock->method('getTable')->willReturn('no matter');

        $this->attributeMock = $this->createPartialMock(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class, ['getAttributeCode', 'getOptions']);
        $this->attributeMock->method('getAttributeCode')->willReturn($this->attributeCode);

        $this->attributeOptionHandler = $this->objectManager->getObject(
            \Marketplacer\Base\Model\Attribute\AttributeOptionHandler::class,
            [
                'productAttributeRepository' => $this->productAttributeRepositoryMock,
                'productAttributeOptionManagementFactory'    => $this->productAttributeOptionManagementFactoryMock,
                'attributeOptionResource'    => $this->attributeOptionResourceMock,
                'attributeOptionFactory'    => $this->attributeOptionFactoryMock,
                'attributeOptionLabelFactory'    => $this->attributeOptionLabelFactoryMock,
            ]
        );
    }

    public function testIsAttributeOptionIdExistExisting() {
        $optionId = 5;

        $option = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option->setValue($optionId);

        $this->attributeMock->method('getOptions')->willReturn([$option]);

        $this->connectionMock->method('fetchAll')->willReturn($this->getOptionsDbDataArray());

        $this->assertTrue($this->attributeOptionHandler->isAttributeOptionIdExist($this->attributeMock, $optionId));
    }

    public function testIsAttributeOptionIdExistMissing() {
        $this->attributeMock->method('getOptions')->willReturn([]);

        $this->assertFalse($this->attributeOptionHandler->isAttributeOptionIdExist($this->attributeMock, 5));
    }

    public function testGetAttributeOptionByIdExisting() {
        $optionId = 5;

        $option = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option->setValue($optionId);

        $this->attributeMock->method('getOptions')->willReturn([$option]);

        $this->connectionMock->method('fetchAll')->willReturn($this->getOptionsDbDataArray());

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $resultOption */
        $resultOption = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $resultOption->setValue($optionId);
        $resultOption->setLabel('test label 5 store_id 0');
        $resultOption->setStoreLabels([
            $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\OptionLabel::class)->setStoreId(1)->setLabel('test label 5 store_id 1')
        ]);

        $this->assertEquals($resultOption, $this->attributeOptionHandler->getAttributeOptionById($this->attributeMock, $optionId));
    }

    public function testGetAttributeOptionByIdMissing() {
        $optionId = 13;

        $this->attributeMock->method('getOptions')->willReturn([]);

        $this->assertNull($this->attributeOptionHandler->getAttributeOptionById($this->attributeMock, $optionId));
    }

    public function testGetAttributeOptionByIdCached() {
        $optionId = 5;

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $cachedOption */
        $cachedOption = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $cachedOption->setValue($optionId);
        $cachedOption->setLabel('test label 5 store_id 0');
        $cachedOption->setStoreLabels([
            $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\OptionLabel::class)->setStoreId(1)->setLabel('test label 5 store_id 1')
        ]);

        $this->setProperty($this->attributeOptionHandler, 'optionsCache', [$this->attributeCode => [$optionId => $cachedOption]]);

        $this->attributeMock->expects($this->never())->method('getOptions');
        $this->connectionMock->expects($this->never())->method('fetchAll');

        $this->assertEquals($cachedOption, $this->attributeOptionHandler->getAttributeOptionById($this->attributeMock, $optionId));
    }

    public function testProtectedGetAttributeOptionsWithStoreLabels() {
        $optionIds = [5, 10, 13];

        $option1 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option1->setValue(5);

        $option2 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option2->setValue(10);

        $this->attributeMock->method('getOptions')->willReturn([$option1, $option2]);
        $this->connectionMock->method('fetchAll')->willReturn($this->getOptionsDbDataArray());

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $resultOption1 */
        $resultOption1 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $resultOption1->setValue(5);
        $resultOption1->setLabel('test label 5 store_id 0');
        $resultOption1->setStoreLabels([
            $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\OptionLabel::class)->setStoreId(1)->setLabel('test label 5 store_id 1')
        ]);

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $resultOption2 */
        $resultOption2 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $resultOption2->setValue(10);
        $resultOption2->setLabel('test label 10 store_id 0');

        $this->assertEquals(
            [5 => $resultOption1, 10 => $resultOption2],
             $this->invokeMethod($this->attributeOptionHandler, 'getAttributeOptionsWithStoreLabels', [$this->attributeMock, $optionIds])
        );
    }

    public function testSaveAttributeOptionExisting() {
        $this->productAttributeOptionManagementMock
            ->expects($this->once())
            ->method('update')
            ->willReturn(true);
        $this->productAttributeOptionManagementMock
            ->expects($this->never())
            ->method('add');

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option->setValue(5);

        $this->attributeOptionHandler->saveAttributeOption($this->attributeMock, $option);
    }

    public function testSaveAttributeOptionNew() {
        $this->productAttributeOptionManagementMock
            ->expects($this->never())
            ->method('update');
        $this->productAttributeOptionManagementMock
            ->expects($this->once())
            ->method('add')
            ->willReturn(true);

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option->setValue(null);

        $this->attributeOptionHandler->saveAttributeOption($this->attributeMock, $option);
    }

    public function testCreateAttributeOption() {
        $emptyOption = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $this->attributeOptionFactoryMock->method('create')->willReturn($emptyOption);

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $resultOption = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $resultOption->setData([
            'is_default' => 0,
            'sort_order' => 0,
            'value' => null,
        ]);

        $this->assertEquals($resultOption, $this->attributeOptionHandler->createAttributeOption());
    }

    public function testGetAttributeOptionsByIds()
    {
        $optionIds = [5, 10, 13];

        //init cache
        $cachedOptionId1 = 5;
        /** @var \Magento\Eav\Model\Entity\Attribute\Option $cachedOption */
        $cachedOption1 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $cachedOption1->setValue($cachedOptionId1)->setLabel('test label 5 store_id 0');
        $cachedOption1->setStoreLabels([
            $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\OptionLabel::class)->setStoreId(1)->setLabel('test label 5 store_id 1')
        ]);

        $cachedOptionId2 = 3;
        /** @var \Magento\Eav\Model\Entity\Attribute\Option $cachedOption */
        $cachedOption2 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $cachedOption2->setValue($cachedOptionId2)->setLabel('extra not used option');

        $this->setProperty(
            $this->attributeOptionHandler,
            'optionsCache',
            [$this->attributeCode => [$cachedOptionId1 => $cachedOption1, $cachedOptionId2 => $cachedOption2]]
        );

        // init attribute options
        $option1 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option1->setValue(5);

        $option2 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option2->setValue(10);

        $option3 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $option3->setValue(150);

        $this->attributeMock->method('getOptions')->willReturn([$option1, $option2, $option3]);
        $this->connectionMock->method('fetchAll')->willReturn($this->getOptionsDbDataArray());

        // init result options
        /** @var \Magento\Eav\Model\Entity\Attribute\Option $resultOption1 */
        $resultOption1 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $resultOption1->setValue(5);
        $resultOption1->setLabel('test label 5 store_id 0');
        $resultOption1->setStoreLabels([
            $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\OptionLabel::class)->setStoreId(1)->setLabel('test label 5 store_id 1')
        ]);

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $resultOption2 */
        $resultOption2 = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);
        $resultOption2->setValue(10);
        $resultOption2->setLabel('test label 10 store_id 0');

        //test method
        $resultRecords = [5 => $resultOption1, 10 => $resultOption2, 13 => null];
        $this->assertEquals(
            $resultRecords,
            $this->attributeOptionHandler->getAttributeOptionsByIds($this->attributeMock, $optionIds)
        );

        //test cache
        $this->assertEquals(
            [
                $this->attributeCode => [
                    3 => $cachedOption2,
                    5 => $resultOption1,
                    10 => $resultOption2,
                    13 => null
                ]
            ],
            $this->getProperty($this->attributeOptionHandler,'optionsCache')
        );
    }

    public function testIsAdminLabelUniqueTrue()
    {
        $this->attributeOptionResourceMock->method('isAdminLabelUnique')->willReturn(true);

        $option = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);

        $this->assertTrue($this->attributeOptionHandler->isAdminLabelUnique($this->attributeMock, $option));
    }

    public function testIsAdminLabelUniqueFalse()
    {
        $this->attributeOptionResourceMock->method('isAdminLabelUnique')->willReturn(false);

        $option = $this->objectManager->getObject(\Magento\Eav\Model\Entity\Attribute\Option::class);

        $this->assertFalse($this->attributeOptionHandler->isAdminLabelUnique($this->attributeMock, $option));
    }

    public function testDeleteOptionByIdSuccessful()
    {
        $this->productAttributeOptionManagementMock->method('delete')->willReturn(true);

        $this->assertTrue($this->attributeOptionHandler->deleteOptionById($this->attributeMock, 5));
    }

    public function testDeleteOptionByIdNotSuccessful()
    {
        $this->productAttributeOptionManagementMock->method('delete')->willReturn(false);

        $this->assertFalse($this->attributeOptionHandler->deleteOptionById($this->attributeMock, 13));
    }

    /**
     * @return array[]
     */
    protected function getOptionsDbDataArray()
    {
        return [
            ['option_id' => 5, 'store_id' => 1, 'value' => 'test label 5 store_id 1'],
            ['option_id' => 5, 'store_id' => 0, 'value' => 'test label 5 store_id 0'],
            ['option_id' => 10, 'store_id' => 0, 'value' => 'test label 10 store_id 0'],
        ];
    }
}
