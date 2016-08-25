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
class Oro_Api_Model_Customer_Address_Api extends Mage_Customer_Model_Api_Resource
{
    /** @var array */
    protected $_mapAttributes = array(
        'customer_address_id' => 'entity_id'
    );

    /**
     * @var Oro_Api_Helper_Data
     */
    protected $_apiHelper;

    /**
     * @var array|null
     */
    protected $_knownApiAttributes;

    public function __construct()
    {
        $this->_apiHelper = Mage::helper('oro_api');
    }

    /**
     * Retrieve customer addresses list
     *
     * @param int $customerId
     * @return array
     */
    public function items($customerId)
    {
        /* @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        return $this->getAddressItems($customer);
    }

    /**
     * Retrieve address data
     *
     * @param int $addressId
     * @return array
     */
    public function info($addressId)
    {
        /** @var Mage_Customer_Model_Address $address */
        $address = Mage::getModel('customer/address')->load($addressId);

        if (!$address->getId()) {
            $this->_fault('address_not_exists');
        }

        return $this->getAddressData($address);
    }

    /**
     * Retrieve customer addresses list
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    public function getAddressItems($customer)
    {
        $result = array();
        /** @var Mage_Customer_Model_Address $address */
        foreach ($customer->getAddresses() as $address) {
            $result[] = $this->getAddressData($address, $customer);
        }

        return $result;
    }

    /**
     * Get customer address data applicable for API response.
     *
     * @param Mage_Customer_Model_Address $address
     * @param Mage_Customer_Model_Customer|null $customer
     * @return array
     */
    public function getAddressData($address, $customer = null)
    {
        if (!$customer) {
            $customer = $address->getCustomer();
        }

        $data = $address->toArray();
        $row = array();

        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
            $row[$attributeAlias] = isset($data[$attributeCode]) ? $data[$attributeCode] : null;
        }

        foreach ($this->getAllowedAttributes($address) as $attributeCode => $attribute) {
            if (isset($data[$attributeCode])) {
                $row[$attributeCode] = $data[$attributeCode];
            }
        }

        $row['attributes'] = $this->_apiHelper
            ->getNotIncludedAttributes($address, $row, $this->_getKnownApiAttributes());
        $row['is_default_billing'] = $customer->getDefaultBilling() == $address->getId();
        $row['is_default_shipping'] = $customer->getDefaultShipping() == $address->getId();

        return $row;
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
                $this->_apiHelper->getComplexTypeScalarAttributes('customerAddressEntityItem'),
                array('entity_id')
            );
        }

        return $this->_knownApiAttributes;
    }
}
