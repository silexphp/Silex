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
use Silex\Route;

/**
 * ControllerCollection test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ControllerCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRouteCollectionWithNoRoutes()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $routes = $controllers->flush();
        $this->assertEquals(0, count($routes->all()));
    }

    public function testGetRouteCollectionWithRoutes()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $controllers->match('/foo', function () {});
        $controllers->match('/bar', function () {});

        $routes = $controllers->flush();
        $this->assertEquals(2, count($routes->all()));
    }

    public function testControllerFreezing()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());

        $fooController = $controllers->match('/foo', function () {})->bind('foo');
        $barController = $controllers->match('/bar', function () {})->bind('bar');

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
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());

        $mountedRootController = $controllers->match('/', function () {});

        $mainRootController = new Controller(new Route('/'));
        $mainRootController->bind($mainRootController->generateRouteName('main_'));

        $controllers->flush();

        $this->assertNotEquals($mainRootController->getRouteName(), $mountedRootController->getRouteName());
    }

    public function testUniqueGeneratedRouteNames()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());

        $controllers->match('/a-a', function () {});
        $controllers->match('/a_a', function () {});

        $routes = $controllers->flush();

        $this->assertCount(2, $routes->all());
        $this->assertEquals(array('_a_a', '_a_a_'), array_keys($routes->all()));
    }

    public function testAssert()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $controllers->assert('id', '\d+');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->assert('name', '\w+')->assert('extra', '.*');
        $controllers->assert('extra', '\w+');

        $this->assertEquals('\d+', $controller->getRoute()->getRequirement('id'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('name'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('extra'));
    }

    public function testValue()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $controllers->value('id', '1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->value('name', 'Fabien')->value('extra', 'Symfony');
        $controllers->value('extra', 'Twig');

        $this->assertEquals('1', $controller->getRoute()->getDefault('id'));
        $this->assertEquals('Fabien', $controller->getRoute()->getDefault('name'));
        $this->assertEquals('Twig', $controller->getRoute()->getDefault('extra'));
    }

    public function testConvert()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $controllers->convert('id', '1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->convert('name', 'Fabien')->convert('extra', 'Symfony');
        $controllers->convert('extra', 'Twig');

        $this->assertEquals(array('id' => '1', 'name' => 'Fabien', 'extra' => 'Twig'), $controller->getRoute()->getOption('_converters'));
    }

    public function testRequireHttp()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $controllers->requireHttp();
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->requireHttps();

        $this->assertEquals(array('https'), $controller->getRoute()->getSchemes());

        $controllers->requireHttp();

        $this->assertEquals(array('http'), $controller->getRoute()->getSchemes());
    }

    public function testBefore()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $controllers->before('mid1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->before('mid2');
        $controllers->before('mid3');

        $this->assertEquals(array('mid1', 'mid2', 'mid3'), $controller->getRoute()->getOption('_before_middlewares'));
    }

    public function testAfter()
    {
        $app = new Application();
        $controllers = new ControllerCollection($app, new Route());
        $controllers->after('mid1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->after('mid2');
        $controllers->after('mid3');

        $this->assertEquals(array('mid1', 'mid2', 'mid3'), $controller->getRoute()->getOption('_after_middlewares'));
    }

    public function testRouteExtension()
    {
        $app = new Application();
        $route = new MyRoute1();

        $controller = new ControllerCollection($app, $route);
        $controller->foo('foo');

        $this->assertEquals('foo', $route->foo);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testRouteMethodDoesNotExist()
    {
        $app = new Application();
        $route = new MyRoute1();

        $controller = new ControllerCollection($app, $route);
        $controller->bar();
    }
}

class MyRoute1 extends Route
{
    public $foo;

    public function foo($value)
    {
        $this->foo = $value;
    }
}
