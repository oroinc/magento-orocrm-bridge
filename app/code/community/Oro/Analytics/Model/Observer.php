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
 * @package   Analytics
 * @copyright Copyright 2013 Oro Inc. (http://www.orocrm.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Oro_Analytics_Model_Observer
{
    /**
     * Add order ids into tracking block to render on order place success page
     *
     * @param Varien_Event_Observer $observer
     */
    public function onOrderSuccessPageView(Varien_Event_Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        $block = Mage::app()->getLayout()->getBlock('oro_analytics');
        if ($block) {
            $block->setOrderIds($orderIds);
        }
    }

    /**
     * Set flag to session that user just registered
     */
    public function onRegistrationSuccess()
    {
        $session = Mage::getSingleton('core/session');
        $session->setData('isJustRegistered', true);
    }

    /**
     * Set product ID to session after product has been added
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCartItemAdded(Varien_Event_Observer $observer)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = $observer->getEvent()->getProduct();

        $session = Mage::getSingleton('checkout/session');
        $session->setData('justAddedProductId', $product->getId());
    }
}
