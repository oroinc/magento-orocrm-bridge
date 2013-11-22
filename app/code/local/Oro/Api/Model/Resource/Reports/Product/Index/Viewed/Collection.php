<?php
/** {license_text}  */ 
class Oro_Api_Model_Resource_Reports_Product_Index_Viewed_Collection
    extends Mage_Reports_Model_Resource_Product_Index_Viewed_Collection
{
    protected function _joinIdxTable()
    {
        if (!$this->getFlag('is_idx_table_joined')) {
            $this->joinTable(
                array('idx_table' => $this->_getTableName()),
                'product_id=entity_id',
                '*',
                $this->_getWhereCondition()
            );
            $this->setFlag('is_idx_table_joined', true);
        }
        return $this;
    }

    /**
     * Retrieve Where Condition to Index table
     *
     * @return array
     */
    protected function _getWhereCondition()
    {

        return array();
    }
}