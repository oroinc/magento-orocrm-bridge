<?php

class Oro_Api_Model_Directory_Region_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * @var Oro_Api_Helper_Data
     */
    protected $_apiHelper;

    public function __construct()
    {
        $this->_apiHelper = Mage::helper('oro_api');
    }


    /**
     * Retrive list of regions
     *
     * @param  object|array $filters
     * @param  \stdClass    $pager
     *
     * @return array
     */
    public function items($filters, $pager)
    {
        /** @var Mage_Directory_Model_Resource_Region_Collection $collection */
        $collection = Mage::getModel('directory/region')->getCollection();

        $filters = $this->_apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $collection->unshiftOrder('region_id', Varien_Data_Collection_Db::SORT_ORDER_ASC);
        if (!$this->_apiHelper->applyPager($collection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        $result = array();
        /** @var Mage_Directory_Model_Region $region */
        foreach ($collection as $region) {
            $result[] = array(
                'region_id' => $region->getId(),
                'code' => $region->getCode(),
                'countryCode' => $region->getCountryId(),
                'name' => $region->getName(),
            );
        }

        return $result;
    }
}
