<?php

class Oro_Api_Model_Ping extends Mage_Api_Model_Resource_Abstract
{
    const VERSION = '1.0.0';
    /**
     * @return array
     */
    public function ping()
    {
        return array(
            'version'      => self::VERSION,
            'mage_version' => Mage::getVersion(),
        );
    }
}
