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
class Oro_Api_Model_Sales_Order_Api extends Mage_Sales_Model_Api_Resource
{
    /**
     * Retrieve list of orders. Filtration could be applied
     *
     * @param null|object|array $filters
     * @param null|\stdClass    $pager
     *
     * @return array
     */
    public function items($filters = null, $pager = null)
    {
        $orders          = array();
        $orderCollection = $this->getOrderCollection();

        /** @var $apiHelper Oro_Api_Helper_Data */
        $apiHelper = Mage::helper('oro_api');
        $filters = $apiHelper->parseFilters($filters, $this->_attributesMap['order']);
        try {
            foreach ($filters as $field => $value) {
                $orderCollection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $orderCollection->setOrder('entity_id');
        if (!$this->applyPager($orderCollection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        foreach ($orderCollection as $order) {
            $orderArray = $this->_getAttributes($order, 'order');

            $orders[] = array_merge($orderArray, $this->info($order));
        }

        return $orders;
    }

    /**
     * Retrieve full order information
     *
     * @param object $order
     * @return array
     */
    protected function info($order)
    {
        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())->getMessage()
            );
        }

        $result = $this->_getAttributes($order, 'order');

        $result['shipping_address'] = $this->_getAttributes($order->getShippingAddress(), 'order_address');
        $result['billing_address']  = $this->_getAttributes($order->getBillingAddress(), 'order_address');
        $result['items'] = array();

        foreach ($order->getAllItems() as $item) {
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(
                    Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage()
                );
            }

            $result['items'][] = $this->_getAttributes($item, 'order_item');
        }

        $result['payment'] = $this->_getAttributes($order->getPayment(), 'order_payment');

        $result['status_history'] = array();

        foreach ($order->getAllStatusHistory() as $history) {
            $result['status_history'][] = $this->_getAttributes($history, 'order_status_history');
        }

        return $result;
    }

    /**
     * @return Mage_Sales_Model_Mysql4_Order_Collection
     */
    protected function getOrderCollection()
    {
        //TODO: add full name logic
        $billingAliasName  = 'billing_o_a';
        $shippingAliasName = 'shipping_o_a';

        /** @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */
        $orderCollection = Mage::getModel("sales/order")->getCollection();

        $billingFirstnameField  = "$billingAliasName.firstname";
        $billingLastnameField   = "$billingAliasName.lastname";
        $shippingFirstnameField = "$shippingAliasName.firstname";
        $shippingLastnameField  = "$shippingAliasName.lastname";

        $orderCollection->addAttributeToSelect('*')
            ->addAddressFields()
            ->addExpressionFieldToSelect(
                'billing_firstname',
                "{{billing_firstname}}",
                array('billing_firstname' => $billingFirstnameField)
            )
            ->addExpressionFieldToSelect(
                'billing_lastname',
                "{{billing_lastname}}",
                array('billing_lastname' => $billingLastnameField)
            )
            ->addExpressionFieldToSelect(
                'shipping_firstname',
                "{{shipping_firstname}}",
                array('shipping_firstname' => $shippingFirstnameField)
            )
            ->addExpressionFieldToSelect(
                'shipping_lastname',
                "{{shipping_lastname}}",
                array('shipping_lastname' => $shippingLastnameField)
            )
            ->addExpressionFieldToSelect(
                'billing_name',
                "CONCAT({{billing_firstname}}, ' ', {{billing_lastname}})",
                array('billing_firstname' => $billingFirstnameField, 'billing_lastname' => $billingLastnameField)
            )
            ->addExpressionFieldToSelect(
                'shipping_name',
                'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
                array('shipping_firstname' => $shippingFirstnameField, 'shipping_lastname' => $shippingLastnameField)
            );

        return $orderCollection;
    }

    /**
     * @param Varien_Data_Collection_Db $collection
     * @param \stdClass|null            $pager
     *
     * @return boolean
     */
    protected function applyPager($collection, $pager)
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
