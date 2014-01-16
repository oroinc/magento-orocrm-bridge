<?php
/**
 * Oro Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is published at http://opensource.org/licenses/osl-3.0.php.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magecore.com so we can send you a copy immediately
 *
 * @category Oro
 * @package Api
 * @copyright Copyright 2013 Oro Inc. (http://www.orocrm.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Oro_Api_Model_Customer_Api extends Mage_Customer_Model_Customer_Api
{
    /**
     * Retrieve customers data
     *
     * @param  object|array $filters
     * @param  array        $pager
     *
     * @return array
     */
    public function items($filters, $pager)
    {
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');

        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('api');

        $filters = $apiHelper->parseFilters($filters, $this->_mapAttributes);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $collection->setOrder('entity_id');
        if (!$this->applyPager($collection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        $result = array();
        foreach ($collection as $customer) {
            $data = $customer->toArray();
            $row  = array();
            foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
                $row[$attributeAlias] = (isset($data[$attributeCode]) ? $data[$attributeCode] : null);
            }
            foreach ($this->getAllowedAttributes($customer) as $attributeCode => $attribute) {
                if (isset($data[$attributeCode])) {
                    $row[$attributeCode] = $data[$attributeCode];
                }
            }

            $result[] = array_merge($row, $this->info($customer));
        }

        return $result;
    }

    /**
     * Retrieve customer data
     *
     * @param       $customer
     * @param array $attributes
     *
     * @return array
     */
    public function info($customer, $attributes = null)
    {
        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        if (!is_null($attributes) && !is_array($attributes)) {
            $attributes = array($attributes);
        }

        $result = array();

        foreach ($this->_mapAttributes as $attributeAlias=>$attributeCode) {
            $result[$attributeAlias] = $customer->getData($attributeCode);
        }

        foreach ($this->getAllowedAttributes($customer, $attributes) as $attributeCode=>$attribute) {
            $result[$attributeCode] = $customer->getData($attributeCode);
        }

        return $result;
    }


    /**
     * @param Mage_Core_Model_Resource_Db_Collection_Abstract $collection
     * @param \stdClass                                       $pager
     *
     * @return boolean
     */
    protected function applyPager(Mage_Core_Model_Resource_Db_Collection_Abstract $collection, \stdClass $pager)
    {
        if ($pager->pageSize && $pager->page) {
            $collection->setCurPage($pager->page);
            $collection->setPageSize($pager->pageSize);

            if ($collection->getCurPage() != $pager->page) {
                return false;
            }
        }

        return true;
    }
}
