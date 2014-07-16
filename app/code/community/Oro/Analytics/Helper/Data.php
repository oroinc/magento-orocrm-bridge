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
class Oro_Analytics_Helper_Data extends Mage_Core_Helper_Abstract
{
    const GUEST_USER_IDENTIFIER = 'guest';

    const EVENT_REGISTRATION_FINISHED = 'registration';
    const EVENT_CART_ITEM_ADDED       = 'cart item added';
    const EVENT_CHECKOUT_STARTED      = 'user entered checkout';
    const EVENT_ORDER_PLACE_SUCCESS   = 'order successfully placed';

    const XML_PATH_ENABLED         = 'oro_analytics/enabled';
    const XML_PATH_HOST            = 'oro_analytics/host';
    const XML_PATH_SITE_IDENTIFIER = 'oro_analytics/site_identifier';

    /**
     * Returns whether analytics is enabled
     *
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->_getConfigValue(self::XML_PATH_ENABLED);
    }

    /**
     * Returns host name from config for analytics service
     *
     * @throws Exception Mismatch of HTTP protocols
     * @return mixed
     */
    public function getHost()
    {
        $secure = Mage::app()->getStore()->isCurrentlySecure();
        $value  = $this->_getConfigValue(self::XML_PATH_HOST);

        if ($secure && !strpos($value, 'https:') !== 0) {
            throw new Exception('Invalid protocol specified, unable to perform tracking');
        }

        return $value;
    }

    /**
     * Returns site identifier for analytics service
     *
     * @return string
     */
    public function getSiteIdentifier()
    {
        return $this->_getConfigValue(self::XML_PATH_SITE_IDENTIFIER);
    }

    /**
     * Returns config value
     *
     * @param string $xmlPath
     *
     * @return mixed
     * @throws Exception Unknown path given
     */
    protected function _getConfigValue($xmlPath)
    {
        // @TODO process real config if needed
        // return Mage::getStoreConfig($xmlPath);

        $value = null;

        switch ($xmlPath) {
            case self::XML_PATH_HOST:
                $value = 'http://crm.dev/';
                break;
            case self::XML_PATH_SITE_IDENTIFIER:
                $value = 'MAGORO';
                break;
            case self::XML_PATH_ENABLED:
                $value = true;
                break;
            default:
                throw new Exception('Invalid config path');
        }

        return $value;
    }
}
