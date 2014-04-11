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
class Oro_Api_Adminhtml_Oro_GatewayController
    extends Mage_Adminhtml_Controller_Action
{
    protected $_publicActions = array('do');

    /**
     * Go to real admin url with session secret key
     */
    public function doAction()
    {
        $request = $this->getRequest();
        $params  = $request->getParams();

        if (isset($params['route'])) {
            $route = $params['route'];
            unset($params['route']);
            $params['is-oro-request'] = true;

            $url = $this->getUrl('adminhtml/' . $route, array('_query' => $params));

            $configFile = Mage::getConfig()->getModuleDir('etc',Mage::helper('oro_api')->getModuleName()) . DS . 'workflow.xml';
            /** @var Mage_Catalog_Model_Config $config */
            $config = Mage::getModel('core/config');
            $config->loadFile($configFile);

            $workFlow = $request->getParam('workflow');

            $endPoints = $config->getXpath("{$workFlow}/end_point_action");
            if (count($endPoints)) {
                $endPoint = (string)array_shift($endPoints);

                Mage::getSingleton('adminhtml/session')->setData('oro_end_point', $endPoint);
                Mage::getSingleton('adminhtml/session')->setData('oro_success_url', $request->getParam('success_url'));
                Mage::getSingleton('adminhtml/session')->setData('oro_error_url', $request->getParam('error_url'));

                $this->_redirectUrl($url);

            } else {
                $this->getResponse()->setBody($this->__('Endpoint not found.'));
            }
        } else {
            $this->getResponse()->setBody($this->__('Please specify route name.'));
        }
    }

    /**
     * Gateway error
     */
    public function errorAction()
    {
        $response = $this->getResponse();

        $response->setBody($this->__('Gateway error.'));
        $response->setHttpResponseCode(400);
    }
}
