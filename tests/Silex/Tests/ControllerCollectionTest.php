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

use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\Controller;
use Silex\ControllerCollection;
use Silex\Exception\ControllerFrozenException;
use Silex\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * ControllerCollection test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ControllerCollectionTest extends TestCase
{
    public function testGetRouteCollectionWithNoRoutes()
    {
        $controllers = new ControllerCollection(new Route());
        $routes = $controllers->flush();
        $this->assertCount(0, $routes->all());
    }

    public function testGetRouteCollectionWithRoutes()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->match('/foo', function () {});
        $controllers->match('/bar', function () {});

        $routes = $controllers->flush();
        $this->assertCount(2, $routes->all());
    }

    public function testControllerFreezing()
    {
        $controllers = new ControllerCollection(new Route());

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
        $controllers = new ControllerCollection(new Route());

        $mountedRootController = $controllers->match('/', function () {});

        $mainRootController = new Controller(new Route('/'));
        $mainRootController->bind($mainRootController->generateRouteName('main_1'));

        $controllers->flush();

        $this->assertNotEquals($mainRootController->getRouteName(), $mountedRootController->getRouteName());
    }

    public function testUniqueGeneratedRouteNames()
    {
        $controllers = new ControllerCollection(new Route());

        $controllers->match('/a-a', function () {});
        $controllers->match('/a_a', function () {});
        $controllers->match('/a/a', function () {});

        $routes = $controllers->flush();

        $this->assertCount(3, $routes->all());
        $this->assertEquals(array('_a_a', '_a_a_1', '_a_a_2'), array_keys($routes->all()));
    }

    public function testUniqueGeneratedRouteNamesAmongMounts()
    {
        $controllers = new ControllerCollection(new Route());

        $controllers->mount('/root-a', $rootA = new ControllerCollection(new Route()));
        $controllers->mount('/root_a', $rootB = new ControllerCollection(new Route()));

        $rootA->match('/leaf', function () {});
        $rootB->match('/leaf', function () {});

        $routes = $controllers->flush();

        $this->assertCount(2, $routes->all());
        $this->assertEquals(array('_root_a_leaf', '_root_a_leaf_1'), array_keys($routes->all()));
    }

    public function testUniqueGeneratedRouteNamesAmongNestedMounts()
    {
        $controllers = new ControllerCollection(new Route());

        $controllers->mount('/root-a', $rootA = new ControllerCollection(new Route()));
        $controllers->mount('/root_a', $rootB = new ControllerCollection(new Route()));

        $rootA->mount('/tree', $treeA = new ControllerCollection(new Route()));
        $rootB->mount('/tree', $treeB = new ControllerCollection(new Route()));

        $treeA->match('/leaf', function () {});
        $treeB->match('/leaf', function () {});

        $routes = $controllers->flush();

        $this->assertCount(2, $routes->all());
        $this->assertEquals(array('_root_a_tree_leaf', '_root_a_tree_leaf_1'), array_keys($routes->all()));
    }

    public function testMountCallable()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->mount('/prefix', function (ControllerCollection $coll) {
            $coll->mount('/path', function ($coll) {
                $coll->get('/part');
            });
        });

        $routes = $controllers->flush();
        $this->assertEquals('/prefix/path/part', current($routes->all())->getPath());
    }

    public function testMountCallableProperClone()
    {
        $controllers = new ControllerCollection(new Route(), new RouteCollection());
        $controllers->get('/');

        $subControllers = null;
        $controllers->mount('/prefix', function (ControllerCollection $coll) use (&$subControllers) {
            $subControllers = $coll;
            $coll->get('/');
        });

        $routes = $controllers->flush();
        $subRoutes = $subControllers->flush();
        $this->assertTrue($routes->count() == 2 && $subRoutes->count() == 0);
    }

    public function testMountControllersFactory()
    {
        $testControllers = new ControllerCollection(new Route());
        $controllers = new ControllerCollection(new Route(), null, function () use ($testControllers) {
            return $testControllers;
        });

        $controllers->mount('/prefix', function ($mounted) use ($testControllers) {
            $this->assertSame($mounted, $testControllers);
        });
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The "mount" method takes either a "ControllerCollection" instance or callable.
     */
    public function testMountCallableException()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->mount('/prefix', '');
    }

    public function testAssert()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->assert('id', '\d+');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->assert('name', '\w+')->assert('extra', '.*');
        $controllers->assert('extra', '\w+');

        $this->assertEquals('\d+', $controller->getRoute()->getRequirement('id'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('name'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('extra'));
    }

    public function testAssertWithMountCallable()
    {
        $controllers = new ControllerCollection(new Route());
        $controller = null;
        $controllers->mount('/{name}', function ($mounted) use (&$controller) {
            $mounted->assert('name', '\w+');
            $mounted->mount('/{id}', function ($mounted2) use (&$controller) {
                $mounted2->assert('id', '\d+');
                $controller = $mounted2->match('/{extra}', function () {})->assert('extra', '\w+');
            });
        });

        $this->assertEquals('\d+', $controller->getRoute()->getRequirement('id'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('name'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('extra'));
    }

    public function testValue()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->value('id', '1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->value('name', 'Fabien')->value('extra', 'Symfony');
        $controllers->value('extra', 'Twig');

        $this->assertEquals('1', $controller->getRoute()->getDefault('id'));
        $this->assertEquals('Fabien', $controller->getRoute()->getDefault('name'));
        $this->assertEquals('Twig', $controller->getRoute()->getDefault('extra'));
    }

    public function testConvert()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->convert('id', '1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->convert('name', 'Fabien')->convert('extra', 'Symfony');
        $controllers->convert('extra', 'Twig');

        $this->assertEquals(array('id' => '1', 'name' => 'Fabien', 'extra' => 'Twig'), $controller->getRoute()->getOption('_converters'));
    }

    public function testRequireHttp()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->requireHttp();
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->requireHttps();

        $this->assertEquals(array('https'), $controller->getRoute()->getSchemes());

        $controllers->requireHttp();

        $this->assertEquals(array('http'), $controller->getRoute()->getSchemes());
    }

    public function testBefore()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->before('mid1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->before('mid2');
        $controllers->before('mid3');

        $this->assertEquals(array('mid1', 'mid2', 'mid3'), $controller->getRoute()->getOption('_before_middlewares'));
    }

    public function testAfter()
    {
        $controllers = new ControllerCollection(new Route());
        $controllers->after('mid1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->after('mid2');
        $controllers->after('mid3');

        $this->assertEquals(array('mid1', 'mid2', 'mid3'), $controller->getRoute()->getOption('_after_middlewares'));
    }

    public function testWhen()
    {
        $controllers = new ControllerCollection(new Route());
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->when('request.isSecure() == true');

        $this->assertEquals('request.isSecure() == true', $controller->getRoute()->getCondition());
    }

    public function testRouteExtension()
    {
        $route = new MyRoute1();

        $controller = new ControllerCollection($route);
        $controller->foo('foo');

        $this->assertEquals('foo', $route->foo);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testRouteMethodDoesNotExist()
    {
        $route = new MyRoute1();

        $controller = new ControllerCollection($route);
        $controller->bar();
    }

    public function testNestedCollectionRouteCallbacks()
    {
        $cl1 = new ControllerCollection(new MyRoute1());
        $cl2 = new ControllerCollection(new MyRoute1());

        $c1 = $cl2->match('/c1', function () {});
        $cl1->mount('/foo', $cl2);
        $c2 = $cl2->match('/c2', function () {});
        $cl1->before('before');
        $c3 = $cl2->match('/c3', function () {});

        $cl1->flush();

        $this->assertEquals(array('before'), $c1->getRoute()->getOption('_before_middlewares'));
        $this->assertEquals(array('before'), $c2->getRoute()->getOption('_before_middlewares'));
        $this->assertEquals(array('before'), $c3->getRoute()->getOption('_before_middlewares'));
    }

    public function testRoutesFactoryOmitted()
    {
        $controllers = new ControllerCollection(new Route());
        $routes = $controllers->flush();
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
    }

    public function testRoutesFactoryInConstructor()
    {
        $app = new Application();
        $app['routes_factory'] = $app->factory(function () {
            return new RouteCollectionSubClass2();
        });

        $controllers = new ControllerCollection(new Route(), $app['routes_factory']);
        $routes = $controllers->flush();
        $this->assertInstanceOf('Silex\Tests\RouteCollectionSubClass2', $routes);
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

class RouteCollectionSubClass2 extends RouteCollection
{
}
