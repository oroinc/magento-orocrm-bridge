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
     * @var array
     */
    protected $_knownAttributes = array();

    /**
     * @var Oro_Api_Helper_Data
     */
    protected $_apiHelper;

    public function __construct()
    {
        $this->_apiHelper = Mage::helper('oro_api');
    }

    /**
     * Retrieve list of quotes. Filtration could be applied
     *
     * @param array|object $filters
     * @param \stdClass    $pager
     *
     * @return array
     */
    public function items($filters, $pager)
    {
        /** @var Mage_Sales_Model_Resource_Quote_Collection $quoteCollection */
        $quoteCollection = Mage::getResourceModel('sales/quote_collection');
        $filters = $this->_apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $quoteCollection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $quoteCollection->setOrder('entity_id', Varien_Data_Collection_Db::SORT_ORDER_ASC);
        if (!$this->_apiHelper->applyPager($quoteCollection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        $this->_preparesGiftMessages($quoteCollection);

        $resultArray = array();
        /** @var Mage_Sales_Model_Quote $quote */
        foreach ($quoteCollection as $quote) {
            $row = $quote->__toArray();
            $attributes = $this->_apiHelper->getNotIncludedAttributes($quote, $row, $this->_getKnownQuoteAttributes());
            if ($attributes) {
                $row['attributes'] = $attributes;
            }
            $row = array_merge($row, $this->info($quote));
            $resultArray[] = $row;
        }

        return $resultArray;
    }

    /**
     * Retrieve full information about quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    protected function info($quote)
    {
        $result                     = $this->_getAttributes($quote, 'quote');
        $result['shipping_address'] = $this->_getAttributes($quote->getShippingAddress(), 'quote_address');
        $result['billing_address']  = $this->_getAttributes($quote->getBillingAddress(), 'quote_address');
        $result['items']            = array();

        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllItems() as $item) {
            $quoteItem = $this->_getAttributes($item, 'quote_item');
            $productAttributes = $this->_getProductAttributes($item);
            $quoteItem = array_merge($quoteItem, $productAttributes);

            $result['items'][] = $quoteItem;
        }

        $result['payment'] = $this->_getAttributes($quote->getPayment(), 'quote_payment');
        if (isset($result['payment'], $result['payment']['additional_information'])
            && is_array($result['payment']['additional_information'])
        ) {
            $result['payment']['additional_information'] = serialize($result['payment']['additional_information']);
        }

        return $result;
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    protected function _getProductAttributes($item)
    {
        $result = array();
        $product = $item->getProduct();

        if ($product) {
            $productImage = $product->getData('image');
            if ($productImage) {
                $result['product_image_url'] = Mage::getSingleton('catalog/product_media_config')
                    ->getMediaUrl($productImage);
            }
            $result['product_url'] = $product->getProductUrl(false);
        }

        return $result;
    }

    /**
     * Get list of attributes exposed to API.
     *
     * @return array
     */
    protected function _getKnownQuoteAttributes()
    {
        if (!$this->_knownAttributes) {
            $this->_knownAttributes = array_merge(
                $this->_apiHelper->getComplexTypeScalarAttributes('salesQuoteEntity'),
                array('entity_id')
            );
        }

        return $this->_knownAttributes;
    }

    /**
     * Set gift_message key to quote and quote item
     *
     * @param Mage_Sales_Model_Resource_Quote_Collection $quoteCollection
     */
    protected function _preparesGiftMessages($quoteCollection)
    {
        $messageIds = array();
        /* @var Mage_Sales_Model_Quote $quote */
        foreach ($quoteCollection as $quote) {
            if ($quote->getGiftMessageId()) {
                $messageIds[] = $quote->getGiftMessageId();
            }
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($quoteItem->getGiftMessageId()) {
                    $messageIds[] = $quoteItem->getGiftMessageId();
                }
            }
        }

        if (!$messageIds) {
            return;
        }

        $messageIds = array_unique($messageIds);

        $giftCollection = Mage::getResourceModel('giftmessage/message_collection');
        $giftCollection->addFieldToFilter('gift_message_id', array('in' => $messageIds));

        /* @var Mage_Sales_Model_Quote $quote */
        foreach ($quoteCollection as $quote) {
            if ($quote->getGiftMessageId()) {
                $quote->setGiftMessage($giftCollection->getItemById($quote->getGiftMessageId())->getMessage());
            }
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($quoteItem->getGiftMessageId()) {
                    $quoteItem->setGiftMessage($giftCollection->getItemById($quoteItem->getGiftMessageId())->getMessage());
                }
            }
        }
    }
}
