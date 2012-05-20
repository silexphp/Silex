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

use Silex\Controller;
use Silex\ControllerCollection;
use Silex\Exception\ControllerFrozenException;

use Symfony\Component\Routing\Route;

/**
 * ControllerCollection test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ControllerCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRouteCollectionWithNoRoutes()
    {
        $controllers = new ControllerCollection();
        $routes = $controllers->flush();
        $this->assertEquals(0, count($routes->all()));
    }

    public function testGetRouteCollectionWithRoutes()
    {
        $controllers = new ControllerCollection();
        $controllers->add(new Controller(new Route('/foo')));
        $controllers->add(new Controller(new Route('/bar')));

        $routes = $controllers->flush();
        $this->assertEquals(2, count($routes->all()));
    }

    public function testControllerFreezing()
    {
        $controllers = new ControllerCollection();

        $fooController = new Controller(new Route('/foo'));
        $fooController->bind('foo');
        $controllers->add($fooController);

        $barController = new Controller(new Route('/bar'));
        $barController->bind('bar');
        $controllers->add($barController);

        $controllers->flush();

        try {
            $fooController->bind('foo2');
            $this->fail();
        } catch (ControllerFrozenException $e) {
        }

        try {
            $barController->bind('bar2');
            $this->fail();
        } catch (ControllerFrozenException $e) {
        }
    }

    public function testConflictingRouteNames()
    {
        $controllers = new ControllerCollection();

        $mountedRootController = new Controller(new Route('/'));
        $controllers->add($mountedRootController);

        $mainRootController = new Controller(new Route('/'));
        $mainRootController->bind($mainRootController->generateRouteName('main_'));

        $controllers->flush();

        $this->assertNotEquals($mainRootController->getRouteName(), $mountedRootController->getRouteName());
    }

    public function testUniqueGeneratedRouteNames()
    {
        $controllers = new ControllerCollection();

        $controllers->add(new Controller(new Route('/a-a')));
        $controllers->add(new Controller(new Route('/a_a')));
        $routes = $controllers->flush();

        $this->assertCount(2, $routes->all());
        $this->assertEquals(array('_a_a', '_a_a_'), array_keys($routes->all()));
    }
}
