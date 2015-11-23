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
class Oro_Api_Model_Customer_Api extends Mage_Customer_Model_Api_Resource
{
    /** @var array */
    protected $_mapAttributes = array(
        'customer_id' => 'entity_id'
    );

    /**
     * Attributes must be processed with source
     *
     * @var array
     */
    protected $_sourcedAttributes = array(
        'gender'
    );

    /**
     * @var Oro_Api_Helper_Data
     */
    protected $_apiHelper;

    /**
     * @var array
     */
    protected $_knownApiAttributes = array();

    /**
     * @var Oro_Api_Model_Customer_Address_Api
     */
    protected $_addressModel;

    public function __construct()
    {
        $this->_apiHelper = Mage::helper('oro_api');
        $this->_addressModel = Mage::getModel('oro_api/customer_address_api');
    }

    /**
     * Create new customer
     *
     * @param array $customerData
     * @return int
     */
    public function create($customerData)
    {
        $customerData = $this->_prepareData($customerData);

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        try {
            $customer->setData($customerData);
            $customer->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return $customer->getId();
    }

    /**
     * Update customer data
     *
     * @param int $customerId
     * @param array $customerData
     * @return boolean
     */
    public function update($customerId, $customerData)
    {
        $customerData = $this->_prepareData($customerData);

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        foreach ($this->getAllowedAttributes($customer) as $attributeCode => $attribute) {
            if (isset($customerData[$attributeCode])) {
                $customer->setData($attributeCode, $customerData[$attributeCode]);
            }
        }

        $customer->save();

        if (!empty($customerData['password'])) {
            $customer->changePassword($customerData['password']);
        }

        return true;
    }

    /**
     * Retrieve customers data
     *
     * @param  object|array $filters
     * @param  \stdClass    $pager
     *
     * @return array
     */
    public function items($filters, $pager)
    {
        /** @var Mage_Customer_Model_Entity_Customer_Collection $collection */
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');

        $filters = $this->_apiHelper->parseFilters($filters, $this->_mapAttributes);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $collection->setOrder('entity_id', Varien_Data_Collection_Db::SORT_ORDER_ASC);
        if (!$this->_apiHelper->applyPager($collection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        $result = array();
        /** @var Mage_Customer_Model_Customer $customer */
        foreach ($collection as $customer) {
            $row = $this->_getCustomerData($customer);

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Retrieve customer data
     *
     * @param int $customerId
     * @param array $attributes
     * @return array
     */
    public function info($customerId, array $attributes = null)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        return $this->_getCustomerData($customer, $attributes);
    }

    /**
     * Get customer data applicable for API response.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param array $allowedAttributes
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _getCustomerData($customer, array $allowedAttributes = null)
    {
        $row = array();

        if (!is_null($allowedAttributes) && !is_array($allowedAttributes)) {
            $allowedAttributes = array($allowedAttributes);
        }

        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
            $row[$attributeAlias] = $customer->getData($attributeCode);
        }

        foreach ($this->getAllowedAttributes($customer, $allowedAttributes) as $attributeCode => $attribute) {
            /** @var $attribute Mage_Customer_Model_Attribute */
            $row[$attributeCode] = $customer->getData($attributeCode);
            if (in_array($attributeCode, $this->_sourcedAttributes)) {
                $attributeValue = $attribute->getSource()->getOptionText($customer->getData($attributeCode));
                $row[$attributeCode] = !empty($attributeValue) ? $attributeValue : null;
            }
        }
        $row['addresses'] = $this->_addressModel->getAddressItems($customer);

        $attributes = $this->_apiHelper->getNotIncludedAttributes(
            $customer,
            $row,
            $this->_getKnownApiAttributes(),
            array('confirmation')
        );
        if ($attributes) {
            $row['attributes'] = $attributes;
        }

        return $row;
    }

    /**
     * @param array|\stdClass $data
     * @return array
     */
    protected function _prepareData($data)
    {
        $data = (array)$data;
        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
            if (isset($data[$attributeAlias])) {
                $data[$attributeCode] = $data[$attributeAlias];
                unset($data[$attributeAlias]);
            }
        }

        return $data;
    }

    /**
     * Get list of attributes exposed to API.
     *
     * @return array
     */
    protected function _getKnownApiAttributes()
    {
        if (!$this->_knownApiAttributes) {
            $this->_knownApiAttributes = array_merge(
                $this->_apiHelper->getComplexTypeScalarAttributes('oroCustomerEntity'),
                array('entity_id', 'default_shipping', 'default_billing')
            );
        }

        return $this->_knownApiAttributes;
    }
}
