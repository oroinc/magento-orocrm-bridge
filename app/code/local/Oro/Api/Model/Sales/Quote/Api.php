<?php
/** {license_text}  */
class Oro_Api_Model_Sales_Quote_Api
    extends Mage_Checkout_Model_Api_Resource
{
    /**
     * @param array|object $filters
     * @return array
     */
    public function items($filters)
    {
        /** @var Mage_Sales_Model_Resource_Quote_Collection $quoteCollection */
        $quoteCollection = Mage::getResourceModel('sales/quote_collection');
        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('api');
        $filters = $apiHelper->parseFilters($filters, $this->_attributesMap['quote']);
        try {
            foreach ($filters as $field => $value) {
                $quoteCollection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $arr = $quoteCollection->toArray();

        return $arr['items'];
    }
}