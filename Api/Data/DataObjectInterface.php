<?php

namespace Marketplacer\Base\Api\Data;

use Magento\Framework\DataObject;

interface DataObjectInterface
{
    /**
     * Add data to the object.
     * @param array $arr
     * @return $this
     * @see \Magento\Framework\DataObject::addData
     *
     */
    public function addData(array $arr);

    /**
     * Overwrite data in the object.
     * @param string|array $key
     * @param mixed $value
     * @return $this
     * @see \Magento\Framework\DataObject::setData
     *
     */
    public function setData($key, $value = null);

    /**
     * Unset data from the object.
     * @param null|string|array $key
     * @return $this
     * @see \Magento\Framework\DataObject::unsetData
     *
     */
    public function unsetData($key = null);

    /**
     * Object data getter
     * @param string $key
     * @param string|int $index
     * @return mixed
     * @see \Magento\Framework\DataObject::getData
     *
     */
    public function getData($key = '', $index = null);

    /**
     * If $key is empty, checks whether there's any data in the object
     * @param string $key
     * @return bool
     * @see \Magento\Framework\DataObject::hasData
     *
     */
    public function hasData($key = '');
}
