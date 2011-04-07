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
use Silex\Extension\DoctrineOrmExtension;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

/**
 * DoctrineOrmExtension test cases.
 *
 * @author Florian Klein <florian.klein@knplabs.com>
 */
class doctrineOrmExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/doctrine/lib')) {
            $this->markTestSkipped('Doctrine submodules were not installed.');
        }
    }

    public function testRegister()
    {
        $app = new Application();

        $app->register(new DoctrineOrmExtension(), array(
            'doctrine.common.class_path'    => __DIR__.'/../../../../vendor/doctrine-common/lib',
            'doctrine.dbal.class_path'    => __DIR__.'/../../../../vendor/doctrine-dbal/lib',
            'doctrine.orm.class_path'    => __DIR__.'/../../../../vendor/doctrine/lib',
            'doctrine.orm.connection_options' => array(
                'driver' => 'pdo_sqlite',
                'path' => ':memory',
            ),
        ));

        $this->assertTrue($app['doctrine.orm.entity_manager'] instanceof EntityManager);

        $driver = $app['doctrine.orm.entity_manager']->getConfiguration()->getMetadataDriverImpl();
        $this->assertTrue($driver instanceof DriverChain);

        $annotationDriver = array_pop($driver->getDrivers());
        $this->assertTrue($annotationDriver instanceof AnnotationDriver);

        $namespace = array_pop(array_keys($driver->getDrivers()));
        $this->assertEquals('Entity', $namespace);
    }
}
