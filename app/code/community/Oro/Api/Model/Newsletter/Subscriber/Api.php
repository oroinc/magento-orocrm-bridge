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
class Oro_Api_Model_Newsletter_Subscriber_Api
    extends Mage_Checkout_Model_Api_Resource
{
    /**
     * Retrieve list of newsletter subscribers. Filtration could be applied
     *
     * @param object|array $filters
     * @param object $pager
     * @return array
     * @throws Mage_Api_Exception
     */
    public function items($filters, $pager)
    {
        /** @var Mage_Newsletter_Model_Resource_Subscriber_Collection $collection */
        $collection = Mage::getResourceModel('newsletter/subscriber_collection');
        /** @var $apiHelper Oro_Api_Helper_Data */
        $apiHelper = Mage::helper('oro_api');
        $filters = $apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $collection->setOrder('subscriber_id', Varien_Data_Collection_Db::SORT_ORDER_DESC);

        if ($apiHelper->applyPager($collection, $pager)) {
            $result = $collection->toArray();

            if (array_key_exists('items', $result)) {
                return $result['items'];
            }
        }

        return array();
    }

    /**
     * Create new newsletter subscriber.
     *
     * @param object|array $subscriberData
     * @return array
     */
    public function create($subscriberData)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = Mage::getModel('newsletter/subscriber');

        $subscriberData = (array)$subscriberData;
        if (empty($subscriberData['subscriber_confirm_code'])) {
            $subscriberData['subscriber_confirm_code'] = $subscriber->randomSequence();
        }

        $hasCustomer = !empty($subscriberData['customer_id']);
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = null;
        if ($hasCustomer) {
            $customer = Mage::getModel('customer/customer')->load($subscriberData['customer_id']);
            if (!$customer->getId()) {
                $this->_fault('customer_not_found');
            }

            $subscriber->loadByCustomer($customer);
            if ($subscriber->getId()) {
                $this->_fault('customer_already_subscribed');
            }
        }
        if (!empty($subscriberData['subscriber_email'])) {
            $subscriber->loadByEmail($subscriberData['subscriber_email']);
            if ($subscriber->getId()) {
                if (!$subscriber->getCustomerId()) {
                    $subscriber->setCustomerId($subscriberData['customer_id']);
                    $subscriber->save();
                } else {
                    $this->_fault('email_already_subscribed');
                }
            }
        } else {
            if ($customer && $customer->getEmail()) {
                $subscriberData['subscriber_email'] = $customer->getEmail();
            } else {
                $this->_fault('email_is_empty');
            }
        }

        // store_id can be 0
        if ($customer && $customer->getStore()
            && (!array_key_exists('store_id', $subscriberData) || strlen($subscriberData['store_id']) === 0)
        ) {
            $subscriberData['store_id'] = $customer->getStore()->getId();
        }

        if (!$subscriber->getId()) {
            $this->_saveSubscriber($subscriber, $subscriberData);
        }

        // Send subscribe confirmation email if needed.
        $isConfirmNeed = Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG) == 1;
        if ($isConfirmNeed) {
            if ($subscriber->getStatus() === Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                $subscriber->sendConfirmationSuccessEmail();
            } elseif ($subscriber->getStatus() === Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                $subscriber->sendConfirmationRequestEmail();
            }
        }

        return $subscriber->toArray();
    }

    /**
     * Update newsletter subscriber data
     *
     * @param int $subscriberId
     * @param object|array $subscriberData
     * @return array
     * @throws Mage_Api_Exception
     */
    public function update($subscriberId, $subscriberData)
    {
        $subscriberData = (array)$subscriberData;

        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = Mage::getModel('newsletter/subscriber')
            ->load($subscriberId);
        if (!$subscriber->getId()) {
            $this->_fault('not_exists');
        }

        if (!empty($subscriberData['customer_id'])) {
            if ($subscriber->getCustomerId() && $subscriber->getCustomerId() != $subscriberData['customer_id']) {
                $this->_fault('subscriber_customer_change_forbidden');
            } elseif (!$subscriber->getCustomerId()) {
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel('customer/customer')->load($subscriberData['customer_id']);
                if (!$customer->getId()) {
                    $this->_fault('customer_not_found');
                }

                $subscriber->loadByCustomer($customer);
                if ($subscriber->getId() != $subscriberId) {
                    $this->_fault('customer_already_subscribed');
                }
            }
        }
        if (!empty($subscriberData['subscriber_email'])) {
            $subscriber->loadByEmail($subscriberData['subscriber_email']);
            if ($subscriber->getId() != $subscriberId) {
                $this->_fault('email_already_subscribed');
            }
        } else {
            unset($subscriberData['subscriber_email']);
        }

        $this->_saveSubscriber($subscriber, $subscriberData);

        return $subscriber->toArray();
    }

    /**
     * Subscribe by email.
     *
     * @param string $email
     * @return array
     * @throws Mage_Api_Exception
     */
    public function subscribeEmail($email)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = Mage::getModel('newsletter/subscriber');
        try {
            $subscriber->subscribe($email);
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return $subscriber->toArray();
    }

    /**
     * Unsubscribe newsletter subscriber.
     *
     * @param int $subscriberId
     * @return bool
     * @throws Mage_Api_Exception
     */
    public function unsubscribe($subscriberId)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = Mage::getModel('newsletter/subscriber')
            ->load($subscriberId);

        if (!$subscriber->getId()) {
            $this->_fault('not_exists');
        }

        try {
            $subscriber->unsubscribe();
        }  catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return true;
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param array $data
     * @throws Exception
     * @throws Mage_Api_Exception
     */
    protected function _saveSubscriber(Mage_Newsletter_Model_Subscriber $subscriber, array $data)
    {
        unset($data['subscriber_id']);
        foreach ($data as $key => $value) {
            $subscriber->setData($key, $value);
        }

        try {
            $subscriber->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
    }
}