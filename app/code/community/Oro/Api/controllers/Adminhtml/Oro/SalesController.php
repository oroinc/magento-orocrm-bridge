<?php
class Oro_Api_Adminhtml_Oro_SalesController
    extends Mage_Adminhtml_Controller_Action
{
    public function newOrderAction()
    {
        $quoteId = $this->getRequest()->getParam('quote');

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadByIdWithoutStore($quoteId);

        if ($quote->getId()) {
            $session = Mage::getSingleton('adminhtml/session_quote');
            $session->clear();
            $session->setStoreId($quote->getStoreId());
            $session->setQuoteId($quote->getId());
            $session->setCustomerId((int) $quote->getCustomerId());

            $this->_redirect('*/sales_order_create/index', array('customer_id' => $quote->getCustomerId()));
        } else {
            $this->_redirect('*/oro_gateway/error');
        }
    }
}
