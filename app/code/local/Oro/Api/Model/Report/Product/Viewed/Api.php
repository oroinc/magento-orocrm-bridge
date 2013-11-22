<?php
/** {license_text}  */
class Oro_Api_Model_Report_Product_Viewed_Api
    extends Mage_Checkout_Model_Api_Resource
{
    /**
     * @param array|object $filters
     * @return array
     */
    public function items($filters)
    {
        /** @var Oro_Api_Model_Resource_Reports_Product_Index_Viewed_Collection $collection */
        $collection = Mage::getResourceModel('oro_api/reports_product_index_viewed_collection');
        $collection->addIndexFilter();
        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('api');
        $filters = $apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        return $collection->load()->toArray();
    }
}
