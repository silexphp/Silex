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
use Silex\Controller;
use Silex\ControllerCollection;
use Silex\Exception\ControllerFrozenException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * ControllerCollection test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ControllerCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRouteCollectionWithNoRoutes()
    {
        $routes = new RouteCollection();
        $controllers = new ControllerCollection($routes);

        $this->assertEquals(0, count($routes->all()));
        $controllers->flush();
        $this->assertEquals(0, count($routes->all()));
    }

    public function testGetRouteCollectionWithRoutes()
    {
        $routes = new RouteCollection();
        $controllers = new ControllerCollection($routes);
        $controllers->add(new Controller(new Route('/foo')));
        $controllers->add(new Controller(new Route('/bar')));

        $this->assertEquals(0, count($routes->all()));
        $controllers->flush();
        $this->assertEquals(2, count($routes->all()));
    }

    public function testAll()
    {
        $controllers = new ControllerCollection(new RouteCollection());
        $controllers->add($c1 = new Controller(new Route('/foo')));
        $controllers->add($c2 = new Controller(new Route('/bar')));

        $this->assertEquals(array($c1, $c2), $controllers->all());
    }

    public function testControllerFreezing()
    {
        $routes = new RouteCollection();
        $controllers = new ControllerCollection($routes);

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
}
