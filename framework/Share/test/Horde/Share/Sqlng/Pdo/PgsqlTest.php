<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

/**
 * Copyright 2010-2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Share_Sqlng_Pdo_PgsqlTest extends Horde_Share_Test_Sqlng_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('pgsql', PDO::getAvailableDrivers())) {
            self::$reason = 'No pgsql extension or no pgsql PDO driver';
            return;
        }
        $config = self::getConfig('SHARE_SQL_PDO_PGSQL_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['share']['sql']['pdo_pgsql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Pgsql($config['share']['sql']['pdo_pgsql']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No pdo_pgsql configuration';
        }
    }
}
