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
class Oro_Api_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function beforeNewsletterSubscriberSave($observer)
    {
        $subscriber = $observer->getSubscriber();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $subscriber['change_status_at'] = $now->format('Y-m-d H:i:s');
    }
}
