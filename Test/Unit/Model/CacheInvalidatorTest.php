<?php

namespace Marketplacer\Base\Test\Unit\Model;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Marketplacer\Base\Api\CacheInvalidatorInterface;
use Marketplacer\BrandApi\Model\BrandManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CacheInvalidatorTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\Cache\TypeList|MockObject
     */
    private $typeListMock;

    /**
     * @var array
     */
    private $defaultTypesToInvalidate = ['block_html', 'full_page'];

    /**
     * @var \Marketplacer\Base\Model\CacheInvalidator
     */
    private $cacheInvalidator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->typeListMock = $this->createMock(\Magento\Framework\App\Cache\TypeList::class);

        $this->cacheInvalidator = $this->objectManager->getObject(
            \Marketplacer\Base\Model\CacheInvalidator::class,
            [
                'typeList' => $this->typeListMock,
                'defaultTypesToInvalidate'    => $this->defaultTypesToInvalidate,
            ]
        );
    }

    public function testInvalidateDefaultList()
    {
        $this->typeListMock
            ->expects($this->once())
            ->method('invalidate')
            ->with($this->defaultTypesToInvalidate);

        $this->cacheInvalidator->invalidate();
    }

    public function testInvalidateExtendedList()
    {
        $customList = ['test1'];

        $this->typeListMock
            ->expects($this->once())
            ->method('invalidate')
            ->with(array_merge($this->defaultTypesToInvalidate, $customList));

        $this->cacheInvalidator->invalidate($customList);
    }
}
