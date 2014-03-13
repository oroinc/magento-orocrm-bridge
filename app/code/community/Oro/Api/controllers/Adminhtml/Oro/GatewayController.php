<?php
class Oro_Api_Adminhtml_Oro_GatewayController
    extends Mage_Adminhtml_Controller_Action
{
    protected $_publicActions = array('go');

    /**
     * Go to real admin url with session secret key
     */
    public function goAction()
    {
        $params = $this->getRequest()->getParams();

        if (isset($params['route'])) {
            $route  = $params['route'];
            unset($params['route']);
            $params['is-oro-request'] = true;

            $url = $this->getUrl('adminhtml/' . $route, array('_query' => $params));

            $this->_redirectUrl($url);
        } else {
            $this->getResponse()->setBody($this->__('Please specify route name.'));
        }
    }

    /**
     * Gateway error
     */
    public function errorAction()
    {
        $this->getResponse()->setBody($this->__('Gateway error.'));
    }
}
