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

/**
 * Oro Wishlist Api
 */
class Oro_Api_Model_Wishlist_Api extends Mage_Api_Model_Resource_Abstract
{
    protected $_mapAttributes = array(
        'wishlist_id' => 'entity_id'
    );

    /**
     * Store ids (website stores)
     *
     * @var array
     */
    protected $storeIds = null;

    /**
     * @var Oro_Api_Helper_Data
     */
    protected $apiHelper;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiHelper = Mage::helper('oro_api');
    }

    /**
     * @param array|object $filters
     * @param \stdClass    $pager
     * @return array
     */
    public function items($filters, $pager = null)
    {
        /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection $collection */
        $collection = Mage::getResourceModel('wishlist/wishlist_collection');
        $filters = $this->apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        if ($pager && !$this->apiHelper->applyPager($collection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        $arr = $collection->toArray();

        return $arr['items'];
    }

    /**
     * Get wishlist with items info
     *
     * @param array|object   $filters
     * @param null|\stdClass $pager
     * @return array
     *
     * @throws Mage_Api_Exception
     */
    public function listWithItems($filters, $pager = null)
    {
        /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection $collection */
        $collection = Mage::getResourceModel('wishlist/wishlist_collection');
        $filters = $this->apiHelper->parseFilters($filters, array('updated_at' => 'main_table.updated_at'));
        /* Prepare store/website filters for wishlist  */
        $filters = $this->prepareFilters($filters);

        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
            // Load customer data
            $collection = $this->addCustomerData($collection);
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        if ($pager && !$this->apiHelper->applyPager($collection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        $result = array();
        foreach ($collection as $wishlist) {
            /** @var Mage_Wishlist_Model_Wishlist $wishlist */
            $wishlistData = $this->info($wishlist);
            $result[] = $wishlistData;
        }

        return $result;
    }

    /**
     * Retrieve store ids
     *
     * @param null|integer $websiteId
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

    /**
     * Add customer data to collection
     *
     * @param Mage_Wishlist_Model_Resource_Wishlist_Collection $collection
     * @return Mage_Wishlist_Model_Resource_Wishlist_Collection
     */
    protected function addCustomerData($collection)
    {
        $customerTable = $collection->getTable('customer/entity');
        $select = $collection->getSelect();
        $select->joinLeft(
            array('customer' => $customerTable),
            'main_table.customer_id = customer.entity_id',
            array(
                'customer_email' => 'email',
                'website_id' => 'website_id'
            )
        );

        return $collection;
    }

    /**
     * Retrieve full information about wishlist
     *
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @return array
     */
    protected function info($wishlist)
    {
        $result = $wishlist->toArray();
        $result['items'] = array();

        // Load wishlist items data
        /** @var Mage_Wishlist_Model_Resource_Item_Collection $collection */
        $collection = Mage::getResourceModel('wishlist/item_collection')
            ->addWishlistFilter($wishlist)
            ->addStoreFilter($this->storeIds)
            ->setVisibilityFilter();

        /** @var Mage_Wishlist_Model_Item $item */
        foreach ($collection as $item) {
            $product = $item->getProduct();
            if ($product) {
                $item->setSku($product->getSku());
                $item->setProductName($product->getName());
                $item->setWebsiteId(Mage::app()->getStore($item->getStoreId())->getWebsiteId());
            }
            $wishlistItem = $item->toArray();
            unset($wishlistItem['product']);
            $result['items'][] = $wishlistItem;
        }
        return $result;
    }

    /**
     * Prepare store/website filters for wishlist
     *
     * Exclude store fields from complex filter for wishlist if exists
     * Save store filter data to storeIds - used for filter wishlist items
     * Save website filter data to websiteIds - used for filter wishlists by customer's website
     *
     * If website filter exists in the incoming filter (from integration) and customer accounts shared globally
     * ignore sent filters, set all stores where customer can be presented to storeIds
     *
     * Example if complex filter:
     * 'website_id',
     * [
     *     'key'   => 'website_id',
     *     'value' => [
     *         'key'   => 'eq',
     *         'value' => '1'
     *      ]
     * ]
     * 'store_id',
     * [
     *     'key' => 'store_id',
     *     'value' => [
     *         'key' => 'in',
     *         'value' => [1,2]
     *     ]
     * ]
     *
     * @param  array $filters
     * @return array
     */
    protected function prepareFilters($filters)
    {
        if (Mage::getSingleton('customer/config_share')->isWebsiteScope()) {
            if (isset($filters['store_id'])) {
                $storeIds = $this->apiHelper->getDataFromFilterCondition($filters['store_id']);
                $this->storeIds = $storeIds;
                unset($filters['store_id']);
            }
            if (isset($filters['website_id'])) {
                $websiteIds = $this->apiHelper->getDataFromFilterCondition($filters['website_id']);
                if (!$this->storeIds) {
                    foreach ($websiteIds as $websiteId) {
                        $storeIds = $this->getStoreIds($websiteId);
                        foreach ($storeIds as $id) {
                            $this->storeIds[] = $id;
                        }
                    }
                }
            }
        } else {
            unset($filters['store_id']);
            unset($filters['website_id']);
        }

        if (!$this->storeIds) {
            $this->storeIds = $this->getStoreIds();
        }

        return $filters;
    }
}
