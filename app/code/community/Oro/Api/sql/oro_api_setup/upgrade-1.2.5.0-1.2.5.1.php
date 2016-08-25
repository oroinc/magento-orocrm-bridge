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
 * @category   Oro
 * @package    Oro_Api
 * @copyright  Copyright 2013 Oro Inc. (http://www.orocrm.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/* @var $installer Oro_Api_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Create table 'oro_api/wishlist_status'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('oro_api/wishlist_status'))
    ->addColumn('wishlist_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Wishlist ID')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
    ), 'Website Id')
    ->addColumn('deleted_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Wishlist Deletion Time')
    ->setComment('Wishlist Deletion Status Table');
$installer->getConnection()->createTable($table);

$installer->endSetup();
