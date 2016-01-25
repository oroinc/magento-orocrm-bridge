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
class Oro_Api_Model_Wishlist_Api
    extends Mage_Checkout_Model_Api_Resource
{
    protected $_mapAttributes = array(
        'wishlist_id' => 'entity_id'
    );

    /**
     * @param array|object $filters
     * @return array
     */
    public function items($filters)
    {
        /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection $collection */
        $collection = Mage::getResourceModel('wishlist/wishlist_collection');
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

    /**
     * Get wishlist with items info
     *
     * @param array|object $filters
     * @param int|null $websiteId
     * @return array
     *
     * @throws Mage_Api_Exception
     */
    public function listWithItems($filters, $websiteId = null)
    {
        /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection $collection */
        $collection = Mage::getResourceModel('wishlist/wishlist_collection');

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

        $result = array();
        $storeIds = $this->getStoreIds($websiteId);
        foreach ($collection as $wishlist) {
            /** @var Mage_Wishlist_Model_Wishlist $wishlist */
            $wishlistData = $this->info($wishlist, $storeIds);
            $result[] = $wishlistData;
        }

        return $result;
    }

    /**
     * Retrieve full information about wishlist
     *
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @param array $storeIds
     * @return array
     */
    protected function info($wishlist, $storeIds)
    {
        // Load customer data
        $customer = Mage::getModel('customer/customer')
            ->load($wishlist->getCustomerId());
        if ($customer) {
            $wishlist->setCustomerEmail($customer->getEmail());
        }
        $result = $wishlist->toArray();
        $result['items'] = array();

        //Load wishlist items data
        /** @var Mage_Wishlist_Model_Resource_Item_Collection $collection */
        $collection = Mage::getResourceModel('wishlist/item_collection')
            ->addWishlistFilter($wishlist)
            ->addStoreFilter($storeIds)
            ->setVisibilityFilter();

        /** @var Mage_Wishlist_Model_Item $item */
        foreach ($collection as $item) {
            $product = $item->getProduct();
            if ($product) {
                $item->setSku($product->getSku());
                $item->setProductName($product->getName());
            }
            $wishlistItem = $item->toArray();
            $result['items'][] = $wishlistItem;
        }
        return $result;
    }

    /**
     * Retrieve store ids for website or all stores if $websiteId is not send
     *
     * @param int $websiteId Use website if exist
     * @return array
     */
    public function getStoreIds($websiteId = null)
    {
        $storeIds = array();
        if ($websiteId) {
            $website = Mage::getModel('core/website')->load($websiteId);
            $storeIds = $website->getStoreIds();
        } else {
            $stores = Mage::app()->getStores();
            foreach ($stores as $store) {
                $storeIds[] = $store->getId();
            }
        }
        return $storeIds;
    }
}
