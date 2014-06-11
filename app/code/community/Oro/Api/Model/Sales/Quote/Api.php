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
class Oro_Api_Model_Sales_Quote_Api
    extends Mage_Checkout_Model_Api_Resource
{
    /**
     * @param array|object $filters
     * @param \stdClass    $pager
     *
     * @return array
     */
    public function items($filters, $pager)
    {
        /** @var Mage_Sales_Model_Resource_Quote_Collection $quoteCollection */
        $quoteCollection = Mage::getResourceModel('sales/quote_collection');

        /** @var $apiHelper Oro_Api_Helper_Data */
        $apiHelper = Mage::helper('oro_api');

        $filters = $apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $quoteCollection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $quoteCollection->setOrder('entity_id', Varien_Data_Collection_Db::SORT_ORDER_ASC);
        if (!$apiHelper->applyPager($quoteCollection, $pager)) {
            // there's no such page, so no results for it
            return array();
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

        $result                     = $this->_getAttributes($quote, 'quote');
        $result['shipping_address'] = $this->_getAttributes($quote->getShippingAddress(), 'quote_address');
        $result['billing_address']  = $this->_getAttributes($quote->getBillingAddress(), 'quote_address');
        $result['items']            = array();

        foreach ($quote->getAllItems() as $item) {
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(
                    Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage()
                );
            }

            $result['items'][] = $this->_getAttributes($item, 'quote_item');
        }

        $result['payment'] = $this->_getAttributes($quote->getPayment(), 'quote_payment');
        if (isset($result['payment'], $result['payment']['additional_information'])
            && is_array($result['payment']['additional_information'])
        ) {
            $result['payment']['additional_information'] = serialize($result['payment']['additional_information']);
        }

        return $result;
    }
}
