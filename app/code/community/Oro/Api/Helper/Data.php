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
class Oro_Api_Helper_Data
    extends Mage_Api_Helper_Data
{
    const XPATH_ATTRIBUTES_ENABLED = 'oro/api/enable_attributes';

    /**
     * @return string
     */
    public function getModuleName()
    {
        return $this->_getModuleName();
    }

    /**
     * Parse filters and format them to be applicable for collection filtration
     *
     * @param null|object|array $filters
     * @param array $fieldsMap Map of field names in format: array('field_name_in_filter' => 'field_name_in_db')
     * @return array
     */
    public function parseFilters($filters, $fieldsMap = null)
    {
        // if filters are used in SOAP they must be represented in array format to be used for collection filtration
        if (is_object($filters)) {
            $parsedFilters = array();
            // parse simple filter
            if (isset($filters->filter) && is_array($filters->filter)) {
                foreach ($filters->filter as $field => $value) {
                    if (is_object($value) && isset($value->key) && isset($value->value)) {
                        $parsedFilters[$value->key] = $value->value;
                    } else {
                        $parsedFilters[$field] = $value;
                    }
                }
            }
            // parse complex filter
            if (isset($filters->complex_filter) && is_array($filters->complex_filter)) {
                $parsedFilters += $this->_parseComplexFilter($filters->complex_filter);
            }

            $filters = $parsedFilters;
        }
        // make sure that method result is always array
        if (!is_array($filters)) {
            $filters = array();
        }
        // apply fields mapping
        if (isset($fieldsMap) && is_array($fieldsMap)) {
            foreach ($filters as $field => $value) {
                if (isset($fieldsMap[$field])) {
                    unset($filters[$field]);
                    $field = $fieldsMap[$field];
                    $filters[$field] = $value;
                }
            }
        }
        return $filters;
    }

    /**
     * Parses complex filter, which may contain several nodes, e.g. when user want to fetch orders which were updated
     * between two dates.
     *
     * @param array $complexFilter
     * @return array
     */
    protected function _parseComplexFilter($complexFilter)
    {
        $parsedFilters = array();

        foreach ($complexFilter as $filter) {
            if (!isset($filter->key) || !isset($filter->value)) {
                continue;
            }

            list($fieldName, $condition) = array($filter->key, $filter->value);
            $conditionName = $condition->key;
            $conditionValue = $condition->value;
            $this->formatFilterConditionValue($conditionName, $conditionValue);

            if (array_key_exists($fieldName, $parsedFilters)) {
                $parsedFilters[$fieldName] += array($conditionName => $conditionValue);
            } else {
                $parsedFilters[$fieldName] = array($conditionName => $conditionValue);
            }
        }

        return $parsedFilters;
    }

    /**
     * Convert condition value from the string into the array
     * for the condition operators that require value to be an array.
     * Condition value is changed by reference
     *
     * @param string $conditionOperator
     * @param string $conditionValue
     */
    public function formatFilterConditionValue($conditionOperator, &$conditionValue)
    {
        if (is_string($conditionOperator) && in_array($conditionOperator, array('in', 'nin', 'finset'))
            && is_string($conditionValue)
        ) {
            $delimiter = ',';
            $conditionValue = explode($delimiter, $conditionValue);
        }
    }

    /**
     * @param Varien_Data_Collection_Db $collection
     * @param \stdClass|null            $pager
     *
     * @return boolean
     */
    public function applyPager($collection, $pager)
    {
        if ($pager->pageSize && $pager->page) {
            $collection->setCurPage($pager->page);
            $collection->setPageSize($pager->pageSize);

            if ($collection->getCurPage() != $pager->page) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isOroRequest()
    {
        return (bool) Mage::registry('is-oro-request');
    }

    /**
     * Get WSDL/WSI complexType attributes by complex type name.
     *
     * @param string $typeName
     * @return array
     */
    public function getComplexTypeAttributes($typeName)
    {
        /** @var Mage_Api_Model_Wsdl_Config $wsdlModel */
        $wsdlModel = Mage::getModel('api/wsdl_config');
        $wsdlModel->init();

        $elements = array();
        if ($this->isComplianceWSI()) {
            $elements = $wsdlModel->getXpath(
                'wsdl:types/xsd:schema/xsd:complexType[@name="' . $typeName . '"]/xsd:sequence/xsd:element'
            );
        } else {
            $typeDefinition = $wsdlModel->getNode('types/schema/complexType@name="' . $typeName . '"/all');
            if ($typeDefinition && $typeDefinition->children()->count() > 0) {
                $elements = $typeDefinition->children();
            }
        }

        $exposedAttributes = array();
        /** @var Mage_Api_Model_Wsdl_Config_Element $definitionNode */
        foreach ($elements as $definitionNode) {
            $name = (string)$definitionNode->getAttribute('name');
            $type = (string)$definitionNode->getAttribute('type');
            $exposedAttributes[$name] = $type;
        }

        return $exposedAttributes;
    }

    /**
     * Get WSDL/WSI complexType scalar attributes by complex type name.
     *
     * @param string $typeName
     * @return array
     */
    public function getComplexTypeScalarAttributes($typeName)
    {
        $scalarTypes = array('xsd:string', 'xsd:int', 'xsd:double', 'xsd:boolean', 'xsd:long');

        $result = array();
        $attributes = $this->getComplexTypeAttributes($typeName);
        foreach ($attributes as $typeName => $type) {
            if (in_array($type, $scalarTypes, true)) {
                $result[] = $typeName;
            }
        }

        return $result;
    }

    /**
     * Get entity attributes that are not not present in known attributes list.
     *
     * @param Varien_Object $entity
     * @param array $data
     * @param array $exclude
     * @param array $include
     * @return array
     */
    public function getNotIncludedAttributes(
        Varien_Object $entity,
        array $data,
        array $exclude = array(),
        array $include = array()
    ) {
        if (!Mage::getStoreConfig(self::XPATH_ATTRIBUTES_ENABLED)) {
            return array();
        }

        $entityData = $entity->__toArray();
        $knownAttributes = array_diff(array_keys($entityData), $exclude);
        $attributesToExpose = array_merge($knownAttributes, $include);

        $attributes = array();

        if (!empty($attributesToExpose)) {
            $attributes = array_intersect_key(
                array_merge($data, $entityData),
                array_combine($attributesToExpose, $attributesToExpose)
            );
        }

        return $this->packAssoc($attributes);
    }

    /**
     * Pack associative array to format supported by API.
     *
     * @param array $data
     * @return array
     */
    public function packAssoc(array $data)
    {
        $result = array();
        foreach ($data as $key => $value) {
            $result[] = array(
                'key' => $key,
                'value' => $value
            );
        }

        return $result;
    }

    /**
     * Get store/wibsite filter data as array from filter condition
     *
     * @param array $condition
     * @return array
     */
    public function getDataFromFilterCondition($condition)
    {
        $result = array();

        if (is_array($condition)) {
            if (isset($condition['eq'])) {
                $result = array($condition['eq']);
            } elseif (isset($condition['in'])) {
                $result = $condition['in'];
            }
        }

        return $result;
    }
}
