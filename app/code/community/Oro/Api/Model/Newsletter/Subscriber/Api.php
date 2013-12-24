<?php
class Oro_Api_Model_Newsletter_Subscriber_Api
    extends Mage_Checkout_Model_Api_Resource
{
    public function items($filters)
    {
        /** @var Mage_Newsletter_Model_Resource_Subscriber_Collection $collection */
        $collection = Mage::getResourceModel('newsletter/subscriber_collection');
        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('oro_api');
        $filters = $apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $arr = $collection->toArray();

        return $arr['items'];
    }
}