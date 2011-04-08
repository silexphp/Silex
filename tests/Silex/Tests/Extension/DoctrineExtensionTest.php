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

use Doctrine\DBAL\Connection;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;

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

        $this->assertTrue($app['doctrine.orm.em'] instanceof EntityManager);

        $driver = $app['doctrine.orm.em']->getConfiguration()->getMetadataDriverImpl();
        $this->assertTrue($driver instanceof DriverChain);

        $drivers = $driver->getDrivers();
        $annotationDriver = $drivers['Entity'];
        $this->assertTrue($annotationDriver instanceof AnnotationDriver);

        $this->assertEquals(array(
            '/path/to/Entities',
            '/path/to/another/dir/for/the/same/namespace'
        ), $drivers['Entity']->getPaths());

        $drivers = $driver->getDrivers();
        $ymlDriver = $drivers['My\\Entity'];
        $this->assertTrue($ymlDriver instanceof YamlDriver);

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
        $this->assertTrue($conn instanceof Connection);
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
