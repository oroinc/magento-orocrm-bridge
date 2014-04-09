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
class Oro_Api_Model_Observer_Crm_Controller
{
    /**
     * Catch Oro requests and set required flags
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleRequest(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Front_Action $controller */
        $controller = $observer->getEvent()->getData('controller_action');

        if (!preg_match('/^.+?\\/oro_gateway\\/clearSession/ui', $controller->getRequest()->getPathInfo())) {
            if (preg_match('/^.+?\\/oro_gateway\\/do$/ui', $controller->getRequest()->getOriginalPathInfo()) || $controller->getRequest()->getParam('is-oro-request') || $controller->getRequest()->getCookie('is-oro-request')) {
                $controller->setFlag('', 'is-oro-request', true);
                if (!Mage::registry('is-oro-request')) {
                    Mage::register('is-oro-request', true);
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function handleResponse(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Front_Action $controller */
        $controller = $observer->getEvent()->getData('controller_action');
        $session = Mage::getSingleton('adminhtml/session');
        if (Mage::helper('oro_api')->isOroRequest()) {
            if ($controller->getFullActionName() == $session->getData('oro_end_point')) {
                Mage::getSingleton('core/cookie')->set('is-oro-request', 0, null, null, null, null, false);
                Mage::getSingleton('adminhtml/session')->getMessages(true);
                $controller->getResponse()->clearHeader('Location');
                $controller->getResponse()->clearBody();
                $controller->getResponse()->appendBody('<script type="text/javascript">setTimeout(function(){location.href = "'.$session->getData('oro_success_url').'"}, 1000)</script>')->sendResponse();
                exit;
            } else {
                Mage::getSingleton('core/cookie')->set('is-oro-request', 1, null, null, null, null, false);
            }
        }
    }

    public function handleRenderLayout(Varien_Event_Observer $observer)
    {
        if (Mage::helper('oro_api')->isOroRequest()) {
            $layout = Mage::app()->getLayout();
            /** @var Mage_Core_Block_Text $script */
            $layout->createBlock('adminhtml/template', 'oro_script', array('template' => 'oro/api/script.phtml'));

            $destination = null;

            switch (true) {
                case $layout->getBlock('form.additional.info') instanceof Mage_Core_Block_Text_List:
                    $destination = $layout->getBlock('form.additional.info');
                    break;
                case $layout->getBlock('before_body_end') instanceof Mage_Core_Block_Text_List:
                    $destination = $layout->getBlock('before_body_end');
                    break;
                case $layout->getBlock('content') instanceof Mage_Core_Block_Text_List:
                    $destination = $layout->getBlock('content');
                    break;
                default:
                    $destination = null;
                    break;
            }

            if ($destination) {
                $destination->insert('oro_script');
            }

            if ($layout->getBlock('root') instanceof Mage_Core_Block_Template) {
                $layout->getBlock('root')->setTemplate('oro/api/page.phtml');
            }
        }
    }
}
