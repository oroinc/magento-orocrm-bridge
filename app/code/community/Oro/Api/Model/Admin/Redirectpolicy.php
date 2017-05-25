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
class Oro_Api_Model_Admin_Redirectpolicy extends Mage_Admin_Model_Redirectpolicy
{
    public function getRedirectUrl(Mage_Admin_Model_User $user, Zend_Controller_Request_Http $request = null,
        $alternativeUrl = null)
    {
        if (!empty($request)
            && $request->getControllerModule() === 'Oro_Api_Adminhtml'
            && $request->getControllerName() === 'oro_gateway') {
            return $alternativeUrl;
        }
        parent::getRedirectUrl($user, $request, $alternativeUrl);
    }
}
