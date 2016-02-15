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
 * Wishlist status resource model
 */
class Oro_Api_Model_Resource_Wishlist_Status extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Primary key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('oro_api/wishlist_status', 'wishlist_id');
    }

    /**
     * Clean status logs
     *
     * @param Oro_Api_Model_Wishlist_Status $object
     * @return Oro_Api_Model_Resource_Wishlist_Status
     */
    public function clean(Oro_Api_Model_Wishlist_Status $object)
    {
        $cleanTime = $object->getLogCleanTime();
        $this->_cleanStatuses($cleanTime);

        return $this;
    }

    /**
     * Clean statuses table
     *
     * @param int $time
     * @return Oro_Api_Model_Resource_Wishlist_Status
     */
    protected function _cleanStatuses($time)
    {
        $writeAdapter   = $this->_getWriteAdapter();
        $timeLimit = $this->formatDate(Mage::getModel('core/date')->gmtTimestamp() - $time);
        $condition = array('deleted_at < ?' => $timeLimit);

        // remove wishlist statuses from oro_api/wishlist_status
        try {
            $writeAdapter->delete($this->getTable('oro_api/wishlist_status'), $condition);
        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }

        return $this;
    }
}
