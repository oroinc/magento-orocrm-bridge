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
 * Wishlist Status Resource Collection
 */
class Oro_Api_Model_Resource_Wishlist_Status_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Resource constructor
     *
     */
    protected function _construct()
    {
        $this->_init('oro_api/wishlist_status');
    }
}
