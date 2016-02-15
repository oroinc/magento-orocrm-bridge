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
 * @category   Oro
 * @package    Oro_Api
 * @copyright  Copyright 2013 Oro Inc. (http://www.orocrm.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Multiple wishlist observer.
 */
class Oro_Api_Model_Wishlist_Observer
{
    /**
     * Log wishlist on manually wishlist deletion
     *
     * @param Varien_Event_Observer $observer
     * @return Oro_Api_Model_Wishlist_Observer
     */
    public function registerWishlistChange(Varien_Event_Observer $observer)
    {
        if (Mage::helper('core')->isModuleEnabled('Enterprise_Wishlist') &&
            Mage::helper('enterprise_wishlist')->isMultipleEnabled()) {
            /** @var Mage_Wishlist_Model_Wishlist $wishlist */
            $wishlist = $observer->getEvent()->getObject();
            $wishlistId = $wishlist->getId();
            if ($wishlistId) {
                try {
                    $websiteId = $this->getWishlistOwnerWebsiteId($wishlist);
                    /** @var Oro_Api_Model_Wishlist_Status $wishlistStatus */
                    $wishlistStatus = Mage::getModel('oro_api/wishlist_status')
                        ->setWishlistId($wishlistId)
                        ->setWebsiteId($websiteId)
                        ->setDeletedAt(Mage::getSingleton('core/date')->gmtDate());
                    $wishlistStatus->save();
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve wishlist owner website
     *
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @return null
     */
    public function getWishlistOwnerWebsiteId($wishlist)
    {
        $customerId = $wishlist->getCustomerId();
        if ($customerId) {
            /** @var Mage_Customer_Model_Customer $owner */
            $owner = Mage::getModel("customer/customer");
            $owner->load($customerId);
            if ($owner->getId()) {
                return $owner->getWebsiteId();
            }
        }

        return null;
    }
}
