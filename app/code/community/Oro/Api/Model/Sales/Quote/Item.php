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
class Oro_Api_Model_Sales_Quote_Item extends Mage_Sales_Model_Quote_Item
{
    /**
     * {@inheritdoc}
     *
     * In addition it fixes Magento bug which causes calling methods on null in case productId is null
     */
    public function checkData()
    {
        if (!$this->getProductId()) {
            return $this;
        }

        return parent::checkData();
    }
}
