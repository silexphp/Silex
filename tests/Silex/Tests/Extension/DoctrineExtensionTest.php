<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests;

use Silex\Application;
use Silex\Extension\DoctrineExtension;


/**
 * DoctrineExtension test cases.
 *
 * @author Florian Klein <florian.klein@knplabs.com>
 */
class DoctrineExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/doctrine/lib')) {
            $this->markTestSkipped('Doctrine submodules were not installed.');
        }
    }

    public function testRegisterORM()
    {
        $app = new Application();

        $app->register(new DoctrineExtension(), array(
            'doctrine.common.class_path'    => __DIR__.'/../../../../vendor/doctrine-common/lib',
            'doctrine.dbal.class_path'    => __DIR__.'/../../../../vendor/doctrine-dbal/lib',
            'doctrine.orm.class_path'    => __DIR__.'/../../../../vendor/doctrine/lib',
            'doctrine.dbal.connection_options' => array(
                'driver' => 'pdo_sqlite',
                'path' => ':memory',
            ),
            'doctrine.orm' => true,
            'doctrine.orm.entities' => array(
                array('type' => 'yml', 'path' => '/path/to/yml/files', 'namespace' => 'My\\Entity'),
                array('type' => 'annotation', 'path' => '/path/to/another/dir/with/entities', 'namespace' => 'Acme\\Entity'),
                array('type' => 'xml', 'path' => '/path/to/xml/files', 'namespace' => 'Your\\Entity'),
                array('type' => 'annotation', 'path' => array(
                    '/path/to/Entities',
                    '/path/to/another/dir/for/the/same/namespace'
                ), 'namespace' => 'Entity'),
            )
        ));

        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $app['doctrine.orm.em']);

        $config = $app['doctrine.configuration'];
        $this->assertInstanceOf('Doctrine\ORM\Configuration', $config);
        $this->assertSame($app['doctrine.orm.em']->getConfiguration(), $config);
        $this->assertSame(spl_object_hash($app['doctrine.orm.em']->getConfiguration()), spl_object_hash($config));

        $driver = $app['doctrine.orm.em']->getConfiguration()->getMetadataDriverImpl();
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\DriverChain', $driver);

        $drivers = $driver->getDrivers();
        $annotationDriver = $drivers['Entity'];
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\AnnotationDriver', $annotationDriver);

        $this->assertEquals(array(
            '/path/to/Entities',
            '/path/to/another/dir/for/the/same/namespace'
        ), $drivers['Entity']->getPaths());

        $drivers = $driver->getDrivers();
        $ymlDriver = $drivers['My\\Entity'];
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\YamlDriver', $ymlDriver);

        $this->assertEquals(array('/path/to/yml/files'), $drivers['My\\Entity']->getPaths());
    }

    public function testRegisterDBAL()
    {
        $app = new Application();

        $app->register(new DoctrineExtension(), array(
            'doctrine.common.class_path'    => __DIR__.'/../../../../vendor/doctrine-common/lib',
            'doctrine.dbal.class_path'    => __DIR__.'/../../../../vendor/doctrine-dbal/lib',
            'doctrine.dbal.connection_options' => array(
                'driver' => 'pdo_sqlite',
                'path' => ':memory',
            ),
        ));

        $this->assertFalse(isset($app['doctrine.orm.em']));

        $conn = $app['doctrine.dbal.connection'];
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $conn);
        $eventManager = $app['doctrine.dbal.event_manager'];
        $this->assertInstanceOf('Doctrine\Common\EventManager', $eventManager);

        $config = $app['doctrine.configuration'];
        $this->assertInstanceOf('Doctrine\DBAL\Configuration', $config);

        $this->assertSame($app['doctrine.dbal.connection']->getConfiguration(), $config);
        $this->assertSame(spl_object_hash($app['doctrine.dbal.connection']->getConfiguration()), spl_object_hash($config));
    }

    public function testRegisterORMButNotDBAL()
    {
        $app = new Application();

        $app->register(new DoctrineExtension(), array(
            'doctrine.common.class_path'    => __DIR__.'/../../../../vendor/doctrine-common/lib',
            'doctrine.orm.class_path'    => __DIR__.'/../../../../vendor/doctrine/lib',
            'doctrine.orm' => true
        ));

        $this->assertFalse(isset($app['doctrine.orm.em']));
        $this->assertFalse(isset($app['doctrine.dbal.connection']));
    }
}
