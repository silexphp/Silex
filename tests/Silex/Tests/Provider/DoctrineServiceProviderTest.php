<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;

/**
 * DoctrineProvider test cases.
 *
 * Fabien Potencier <fabien@symfony.com>
 */
class DoctrineServiceProviderTest extends TestCase
{
    public function testOptionsInitializer()
    {
        $app = new Application();
        $app->register(new DoctrineServiceProvider());

        $this->assertEquals($app['db.default_options'], $app['db']->getParams());
    }

    public function testSingleConnection()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('pdo_sqlite is not available');
        }

        $app = new Application();
        $app->register(new DoctrineServiceProvider(), array(
            'db.options' => array('driver' => 'pdo_sqlite', 'memory' => true),
        ));

        $db = $app['db'];
        $params = $db->getParams();
        $this->assertArrayHasKey('memory', $params);
        $this->assertTrue($params['memory']);
        $this->assertInstanceof('Doctrine\DBAL\Driver\PDOSqlite\Driver', $db->getDriver());
        $this->assertEquals(22, $app['db']->fetchColumn('SELECT 22'));

        $this->assertSame($app['dbs']['default'], $db);
    }

    public function testMultipleConnections()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('pdo_sqlite is not available');
        }

        $app = new Application();
        $app->register(new DoctrineServiceProvider(), array(
            'dbs.options' => array(
                'sqlite1' => array('driver' => 'pdo_sqlite', 'memory' => true),
                'sqlite2' => array('driver' => 'pdo_sqlite', 'path' => sys_get_temp_dir().'/silex'),
            ),
        ));

        $db = $app['db'];
        $params = $db->getParams();
        $this->assertArrayHasKey('memory', $params);
        $this->assertTrue($params['memory']);
        $this->assertInstanceof('Doctrine\DBAL\Driver\PDOSqlite\Driver', $db->getDriver());
        $this->assertEquals(22, $app['db']->fetchColumn('SELECT 22'));

        $this->assertSame($app['dbs']['sqlite1'], $db);

        $db2 = $app['dbs']['sqlite2'];
        $params = $db2->getParams();
        $this->assertArrayHasKey('path', $params);
        $this->assertEquals(sys_get_temp_dir().'/silex', $params['path']);
    }

    public function testLoggerLoading()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('pdo_sqlite is not available');
        }

        $app = new Application();
        $this->assertTrue(isset($app['logger']));
        $this->assertNull($app['logger']);
        $app->register(new DoctrineServiceProvider(), array(
            'dbs.options' => array(
                'sqlite1' => array('driver' => 'pdo_sqlite', 'memory' => true),
            ),
        ));
        $this->assertEquals(22, $app['db']->fetchColumn('SELECT 22'));
        $this->assertNull($app['db']->getConfiguration()->getSQLLogger());
    }

    public function testLoggerNotLoaded()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('pdo_sqlite is not available');
        }

        $app = new Container();
        $app->register(new DoctrineServiceProvider(), array(
            'dbs.options' => array(
                'sqlite1' => array('driver' => 'pdo_sqlite', 'memory' => true),
            ),
        ));
        $this->assertEquals(22, $app['db']->fetchColumn('SELECT 22'));
        $this->assertNull($app['db']->getConfiguration()->getSQLLogger());
    }
}
