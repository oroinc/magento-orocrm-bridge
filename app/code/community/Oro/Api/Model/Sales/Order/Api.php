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
    /** @var array */
    protected $_attributesMap = array(
        'order' => array('order_id' => 'entity_id'),
        'order_address' => array('address_id' => 'entity_id'),
        'global' => array()
    );

    /**
     * @var Oro_Api_Helper_Data
     */
    protected $_apiHelper;

    /**
     * @var array
     */
    protected $_knownApiAttributes = array();

    public function __construct()
    {
        $this->_apiHelper = Mage::helper('oro_api');
    }

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

        $filters = $this->_apiHelper->parseFilters($filters, $this->_attributesMap['order']);
        try {
            foreach ($filters as $field => $value) {
                $orderCollection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $orderCollection->setOrder('entity_id', Varien_Data_Collection_Db::SORT_ORDER_ASC);
        if (!$this->_apiHelper->applyPager($orderCollection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        foreach ($orderCollection as $order) {
            $orders[] = $this->_getOrderData($order);
        }

        return $orders;
    }

    /**
     * Retrieve full order information
     *
     * @param string $orderIncrementId
     * @return array
     */
    public function info($orderIncrementId)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderIncrementId);

        if (!$order->getId()) {
            $this->_fault('not_exists');
        }

        return $this->_getOrderData($order);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getOrderData($order)
    {
        /** @var array $orderData */
        $orderData = $this->_getAttributes($order, 'order');

        $attributes = $this->_apiHelper->getNotIncludedAttributes($order, $orderData, $this->getKnownApiAttributes());
        if ($attributes) {
            $orderData['attributes'] = $attributes;
        }
        $orderData = array_merge($orderData, $this->_getOrderAdditionalInfo($order));

        return $orderData;
    }

    /**
     * Retrieve detailed order information
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getOrderAdditionalInfo($order)
    {
        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())->getMessage()
            );
        }

        $result = array();
        $result['shipping_address'] = $this->_getAttributes($order->getShippingAddress(), 'order_address');
        $result['billing_address']  = $this->_getAttributes($order->getBillingAddress(), 'order_address');
        $result['items'] = array();

        /** @var Mage_Sales_Model_Order_Item $item */
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

        $result['coupon_code'] = $order->getCouponCode();

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
     * Get list of attributes exposed to API.
     *
     * @return array
     */
    protected function getKnownApiAttributes()
    {
        if (!$this->_knownApiAttributes) {
            $this->_knownApiAttributes = array_merge(
                $this->_apiHelper->getComplexTypeScalarAttributes('salesOrderEntity'),
                array('entity_id')
            );
        }

        return $this->_knownApiAttributes;
    }
}
