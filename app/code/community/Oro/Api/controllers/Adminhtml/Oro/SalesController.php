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
class Oro_Api_Adminhtml_Oro_SalesController
    extends Mage_Adminhtml_Controller_Action
{
    public function newOrderAction()
    {
        $quote   = false;
        $session = Mage::getSingleton('adminhtml/session_quote');
        $session->clear();

        $quoteId = $this->getRequest()->getParam('quote');
        if (null !== $quoteId) {
            $quote = $this->_getQuoteById($quoteId);
        }

        $customerId = $this->getRequest()->getParam('customer');
        if (null !== $customerId && $this->_checkCustomer($customerId)) {
            $session->setQuoteId(null);
            $session->setCustomerId((int)$customerId);
        } elseif (false !== $quote) {
            $customerId = (int)$quote->getCustomerId();

            $session->setStoreId($quote->getStoreId());
            $session->setQuoteId($quote->getId());
            $session->setCustomerId($customerId);
        } else {
            return $this->_redirect('*/oro_gateway/error');
        }

        return $this->_redirect('*/sales_order_create/index', array('customer_id' => $customerId));
    }

    /**
     * Load customer's frontend quote by given ID
     *
     * @param int $quoteId
     *
     * @return bool|Mage_Sales_Model_Quote
     */
    protected function _getQuoteById($quoteId)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadByIdWithoutStore($quoteId);

        return $quote->getId() ? $quote : false;
    }

    /**
     * Checks whether customer exists
     *
     * @param int $customerId
     *
     * @return bool
     */
    private function _checkCustomer($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);

        return (bool)$customer->getId();
    }
}
