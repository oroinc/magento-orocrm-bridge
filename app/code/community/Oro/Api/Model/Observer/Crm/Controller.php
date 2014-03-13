<?php
/** {license_text}  */
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
        if ($controller->getRequest()->getOriginalPathInfo() == '/admin/oro_gateway/go' || $controller->getRequest()->getParam('is-oro-request') || $controller->getRequest()->getCookie('is-oro-request')) {
            $controller->setFlag('', 'is-oro-request', true);
            if (!Mage::registry('is-oro-request')) {
                Mage::register('is-oro-request', true);
            }
        }


//        /** @var Mage_Core_Controller_Front_Action $controller */
//        $controller = $observer->getEvent()->getData('controller_action');
//        if ($controller->getRequest()->getHeader('oro-request')) {
//            $controller->setFlag('', 'is-oro-request', true);
//            $controller->setFlag('', 'no-renderLayout', true);
//        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function handleResponse(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Front_Action $controller */
        $controller = $observer->getEvent()->getData('controller_action');
        if (!$controller->getResponse()->isRedirect()) {
            unset($_COOKIE['is-oro-request']);
            setcookie('is-oro-request', null, -1, '/');
        }
        //if ($controller->getRequest()->getParam('ororequest')) {
            /** @var Mage_Core_Block_Text $script */
            //$script = $controller->getLayout()->createBlock('core/template', 'oro_script');
            //echo '<script type="text/javascript" src="/js/mage/cookies.js">window.onbeforeunload = function() {window.Mage.Cookies.set("is-oro-request", 1);return "234";}</script>';
            //echo '<script type="text/javascript">window.onbeforeunload = function() {window.Mage.Cookies.set("oro", 1);}</script>';
        //}
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