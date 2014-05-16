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
    protected $_mapAttributes = array(
        'customer_id' => 'entity_id'
    );

    protected $_mapAddressAttributes = array(
        'customer_address_id' => 'entity_id'
    );

    /**
     * Attributes must be processed with source
     *
     * @var array
     */
    protected $_sourcedAttributes = array(
        'gender',
    );

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
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');

        /** @var $apiHelper Oro_Api_Helper_Data */
        $apiHelper = Mage::helper('oro_api');

        $filters = $apiHelper->parseFilters($filters, $this->_mapAttributes);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        $collection->setOrder('entity_id', Varien_Data_Collection_Db::SORT_ORDER_ASC);
        if (!$apiHelper->applyPager($collection, $pager)) {
            // there's no such page, so no results for it
            return array();
        }

        $result = array();
        foreach ($collection as $customer) {
            $row  = array();

            foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
                $row[$attributeAlias] = $customer->getData($attributeCode);
            }

            foreach ($this->getAllowedAttributes($customer) as $attributeCode => $attribute) {
                /** @var $attribute Mage_Customer_Model_Attribute */
                $row[$attributeCode] = $customer->getData($attributeCode);
                if (in_array($attributeCode, $this->_sourcedAttributes)) {
                    $row[$attributeCode] = $attribute->getSource()->getOptionText($customer->getData($attributeCode));
                }
            }

            $row['addresses'] = $this->getAddressItems($customer);
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Retrieve customer addresses list
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return array
     */
    protected function getAddressItems($customer)
    {
        $result = array();
        foreach ($customer->getAddresses() as $address) {
            $data = $address->toArray();
            $row  = array();

            foreach ($this->_mapAddressAttributes as $attributeAlias => $attributeCode) {
                $row[$attributeAlias] = isset($data[$attributeCode]) ? $data[$attributeCode] : null;
            }

            foreach ($this->getAllowedAttributes($address) as $attributeCode => $attribute) {
                if (isset($data[$attributeCode])) {
                    $row[$attributeCode] = $data[$attributeCode];
                }
            }

            $row['is_default_billing'] = $customer->getDefaultBilling() == $address->getId();
            $row['is_default_shipping'] = $customer->getDefaultShipping() == $address->getId();

            $result[] = $row;
        }

        return $result;
    }
}
