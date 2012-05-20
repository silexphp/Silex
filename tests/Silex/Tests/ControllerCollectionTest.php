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
        $r = new \ReflectionObject($mainRootController);
        $m = $r->getMethod('generateRouteName');
        $m->setAccessible(true);
        $mainRootController->bind($m->invoke($mainRootController, 'main_'));

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

    public function testRouteSettingsFromCollection()
    {
        $controller = new Controller(new Route('/'));
        $controller
            ->value('bar', 'bar')
            ->value('baz', 'baz')
            ->assert('bar', 'bar')
            ->assert('baz', 'baz')
            ->convert('bar', $converterBar = function () {})
            ->middleware($middleware1 = function () {})
            ->bind('home')
        ;
        $controller1 = new Controller(new Route('/'));
        $controller1
            ->requireHttp()
            ->method('post')
            ->convert('foo', $converterFoo1 = function () {})
            ->bind('home1')
        ;

        $controllers = new ControllerCollection();
        $controllers->add($controller);
        $controllers->add($controller1);

        $controllers
            ->value('foo', 'foo')
            ->value('baz', 'not_used')
            ->assert('foo', 'foo')
            ->assert('baz', 'not_used')
            ->requireHttps()
            ->method('get')
            ->convert('foo', $converterFoo = function () {})
            ->middleware($middleware2 = function () {})
        ;

        $routes = $controllers->flush();

        $this->assertEquals(array(
            'foo' => 'foo',
            'bar' => 'bar',
            'baz' => 'baz',
        ), $routes->get('home')->getDefaults());

        $this->assertEquals(array(
            'foo'     => 'foo',
            'bar'     => 'bar',
            'baz'     => 'baz',
            '_scheme' => 'https',
            '_method' => 'get',
        ), $routes->get('home')->getRequirements());

        $this->assertEquals(array(
            'foo' => $converterFoo,
            'bar' => $converterBar,
        ), $routes->get('home')->getOption('_converters'));

        $this->assertEquals(array($middleware1, $middleware2), $routes->get('home')->getOption('_middlewares'));

        $this->assertEquals('http', $routes->get('home1')->getRequirement('_scheme'));
        $this->assertEquals('post', $routes->get('home1')->getRequirement('_method'));
        $this->assertEquals(array('foo' => $converterFoo1), $routes->get('home1')->getOption('_converters'));
    }
}
