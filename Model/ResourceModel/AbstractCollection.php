<?php

namespace Marketplacer\Base\Model\ResourceModel;

use Exception;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection as BaseAbstractCollection;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

/**
 * Class AbstractCollection
 * @package Marketplacer\Base\Model\ResourceModel
 */
class AbstractCollection extends BaseAbstractCollection implements SearchResultInterface
{
    /**
     * @var string
     */
    protected $_idEntityKey;

    /**
     * @var int | string
     */
    protected $_singleStoreIdFilter;

    /**
     * @var SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * @var AggregationInterface
     */
    protected $aggregations;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);

        $this->addFilterToMap('name', 'eav_aov.value');
        $this->addFilterToMap('sort_order', 'eav_ao.sort_order');
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('row_id', 'main_table.row_id');
        $this->addFilterToMap('store_id', 'main_table.store_id');
        $this->addFilterToMap('url_key', 'main_table.url_key');
    }

    /**
     * Add field filter to collection
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     * @see self::_getConditionSql for $condition
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ((is_array($field) && in_array('store_id', $field)) || $field === 'store_id') {
            return $this->addStoreIdToFilter($condition);
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add filter by store
     *
     * @param mixed $storeIdCondition
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreIdToFilter($storeIdCondition, $withAdmin = true)
    {
        $condKey = is_array($storeIdCondition) ? array_key_first($storeIdCondition) : null;

        if (is_scalar($storeIdCondition) && $storeIdCondition != Store::DEFAULT_STORE_ID) {
            $this->_singleStoreIdFilter = $storeIdCondition;
        } elseif (is_array($storeIdCondition) && 'eq' === $condKey && Store::DEFAULT_STORE_ID != $storeIdCondition[$condKey]) {
            $this->_singleStoreIdFilter = $storeIdCondition[$condKey];
        }

        if ($withAdmin) {
            if (is_array($storeIdCondition) && count($storeIdCondition) == 1) {
                if (is_numeric($condKey)) {
                    //simple array of ids
                    $storeIdCondition[] = Store::DEFAULT_STORE_ID;
                    $storeIdCondition = ['in' => $storeIdCondition];
                } elseif ($condKey == 'eq' || $condKey == 'in') {
                    //condition array like ['eq' => 1] or ['in' => [1,2]]
                    $value = $storeIdCondition[$condKey] ?? [];
                    $value = (array)$value;
                    $value[] = Store::DEFAULT_STORE_ID;
                    $storeIdCondition = ['in' => $value];
                }
            } elseif (is_scalar($storeIdCondition)) {
                $storeIdCondition = (array)$storeIdCondition;
                $storeIdCondition[] = Store::DEFAULT_STORE_ID;
                $storeIdCondition = ['in' => $storeIdCondition];
            }
        }

        parent::addFieldToFilter('store_id', $storeIdCondition);

        $this->setFlag('_store_filter_applied', true);

        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * Set search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return BaseAbstractCollection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return BaseAbstractCollection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        $this->_totalRecords = $totalCount;
        return $this;
    }

    /**
     * Set items list.
     *
     * @param DataObject[] $items
     * @return BaseAbstractCollection
     * @throws Exception
     */
    public function setItems(array $items = null)
    {
        if (!$items) {
            return $this;
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * {@inheritdoc}
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->joinAttributeOptionColumns();
    }

    /**
     * @return self
     */
    protected function joinAttributeOptionColumns()
    {
        $this->getSelect()->reset(Select::COLUMNS)
            ->columns()
            ->joinLeft(
                ['eav_aov' => $this->getTable('eav_attribute_option_value')],
                'main_table.option_id = eav_aov.option_id AND main_table.store_id = eav_aov.store_id',
                ['name' => 'eav_aov.value']
            )
            ->joinLeft(
                ['eav_ao' => $this->getTable('eav_attribute_option')],
                'eav_aov.option_id = eav_ao.option_id',
                ['sort_order' => 'eav_ao.sort_order']
            );
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeLoad()
    {
        if (!$this->getFlag('_store_filter_applied')) {
            $this->addStoreIdToFilter(Store::DEFAULT_STORE_ID);
        }

        if ($this->_singleStoreIdFilter && $this->_idEntityKey !== null) {
            $subQuery = $this->getConnection()
                ->select()
                ->from($this->getMainTable(), $this->_idEntityKey)
                ->where('store_id = ?', $this->_singleStoreIdFilter);

            $storeWhere = new Zend_Db_Expr('main_table.store_id = ' . $this->_singleStoreIdFilter);
            $entityKeyWhere = new Zend_Db_Expr('main_table.' . $this->_idEntityKey . ' NOT IN ?');
            $this->getSelect()
                ->where($storeWhere . ' OR ' . $entityKeyWhere, $subQuery)
                ->group('main_table.' . $this->_idEntityKey);
        }

        return parent::_beforeLoad();
    }
}
