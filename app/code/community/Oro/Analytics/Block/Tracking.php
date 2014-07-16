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
 * @category  Oro
 * @package   Api
 * @copyright Copyright 2013 Oro Inc. (http://www.orocrm.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * @method array getOrderIds()
 * @method void  setOrderIds(array $orderIds)
 * @method void  setIsCheckoutPage(bool $value)
 * @method bool  getIsCheckoutPage()
 */
class Oro_Analytics_Block_Tracking extends Mage_Core_Block_Template
{
    /**
     * Returns user identifier
     *
     * @return string
     */
    protected function _getUserIdentifier()
    {
        $session = Mage::getModel('customer/session');

        $data = array('id' => null, 'email' => null);
        if ($session->isLoggedIn()) {
            $customer = $session->getCustomer();
            $data     = array('id' => $customer->getId(), 'email' => $customer->getEmail());
        } else {
            $data['id'] = Oro_Analytics_Helper_Data::GUEST_USER_IDENTIFIER;
        }

        return urldecode(http_build_query($data, '', '; '));
    }

    /**
     * Render information about specified orders
     *
     * @return string
     */
    protected function _getOrderEventsData()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return '';
        }

        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));

        $result = array();
        /** @var $order Mage_Sales_Model_Order */
        foreach ($collection as $order) {
            $result[] = sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', '%f' ]);",
                Oro_Analytics_Helper_Data::EVENT_ORDER_PLACE_SUCCESS,
                $order->getSubtotal()
            );
        }

        return implode("\n", $result);
    }

    /**
     * Render information about cart on checkout index page
     *
     * @return string
     */
    protected function _getCheckoutEventsData()
    {
        if ($this->getIsCheckoutPage()) {
            /** @var $quote Mage_Sales_Model_Quote */
            $quote = Mage::getModel('checkout/session')->getQuote();
            $this->setIsCheckoutPage(false);

            return sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', '%f' ]);",
                Oro_Analytics_Helper_Data::EVENT_CHECKOUT_STARTED,
                $quote->getSubtotal()
            );
        }

        return '';
    }

    /**
     * Render information about cart items added
     *
     * @return string
     */
    protected function _getCartEventsData()
    {
        $session = Mage::getSingleton('checkout/session');

        if ($session->hasData('justAddedProductId')) {
            $productId = $session->getData('justAddedProductId');
            $session->unsetData('justAddedProductId');

            return sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', '%d' ]);",
                Oro_Analytics_Helper_Data::EVENT_CART_ITEM_ADDED,
                $productId
            );
        }

        return '';
    }

    /**
     * Renders information about event on register success
     *
     * @return string
     */
    protected function _getCustomerEventsData()
    {
        $session = Mage::getSingleton('core/session');

        if ($session->getData('isJustRegistered')) {
            $session->unsetData('isJustRegistered');

            return sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', 1 ]);",
                Oro_Analytics_Helper_Data::EVENT_REGISTRATION_FINISHED
            );
        }

        return '';
    }

    /**
     * Render tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('oro_analytics')->isEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }
}
