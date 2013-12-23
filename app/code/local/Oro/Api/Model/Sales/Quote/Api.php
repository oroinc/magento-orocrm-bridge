<?php
/** {license_text}  */
class Oro_Api_Model_Sales_Quote_Api
    extends Mage_Checkout_Model_Api_Resource
{
    /**
     * @param array|object $filters
     * @param array $pager
     * @return array
     */
    public function items($filters, $pager)
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

        if ($pager->pageSize && $pager->page) {
            $quoteCollection->setCurPage($pager->page);
            $quoteCollection->setPageSize($pager->pageSize);

            if ($quoteCollection->getCurPage() != $pager->page) {
                // there's no such page, so no results for it
                return array();
            }
        }

        $resultArray = array();
        foreach ($quoteCollection as $quote) {
            $resultArray[] = array_merge($quote->__toArray(), $this->info($quote));
        }

        return $resultArray;
    }

    /**
     * Retrieve full information about quote
     *
     * @param  $quote
     * @return array
     */
    protected function info($quote)
    {
        if ($quote->getGiftMessageId() > 0) {
            $quote->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($quote->getGiftMessageId())->getMessage()
            );
        }

        $result = $this->_getAttributes($quote, 'quote');
        $result['shipping_address'] = $this->_getAttributes($quote->getShippingAddress(), 'quote_address');
        $result['billing_address'] = $this->_getAttributes($quote->getBillingAddress(), 'quote_address');
        $result['items'] = array();

        foreach ($quote->getAllItems() as $item) {
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(
                    Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage()
                );
            }

            $result['items'][] = $this->_getAttributes($item, 'quote_item');
        }

        $result['payment'] = $this->_getAttributes($quote->getPayment(), 'quote_payment');

        return $result;
    }
}
