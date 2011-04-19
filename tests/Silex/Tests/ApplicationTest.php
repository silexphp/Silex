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

use Symfony\Component\HttpFoundation\Request;

/**
 * Application test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchReturnValue()
    {
        $app = new Application();

        $returnValue = $app->match('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->get('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->post('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->put('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->delete('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);
    }

    public function testActionCollection()
    {
        $app = new Application();

        $action1Called = false;
        $action1 = function() use(&$action1Called) {
            $action1Called = true;
        };

        $app->get('/', array($action1, function() {
            return 'Hello, world!';
        }));

        $request = Request::create('/');

        $this->assertEquals('Hello, world!', $app->handle($request)->getContent());
        $this->assertTrue($action1Called);
    }

    public function testGetRequest()
    {
        $app = new Application();

        $app->get('/', function() {
            return 'root';
        });

        $request = Request::create('/');

        $app->handle($request);

        $this->assertEquals($request, $app['request']);
    }

    public function testgetRoutesWithNoRoutes()
    {
        $app = new Application();

        $routes = $app['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertEquals(0, count($routes->all()));
    }

    public function testgetRoutesWithRoutes()
    {
        $app = new Application();

        $app->get('/foo', function() {
            return 'foo';
        });

        $app->get('/bar', function() {
            return 'bar';
        });

        $routes = $app['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertEquals(0, count($routes->all()));
        $app->flush();
        $this->assertEquals(2, count($routes->all()));
    }

    /**
    * @dataProvider escapeProvider
    */
    public function testEscape($expected, $text)
    {
        $app = new Application();

        $this->assertEquals($expected, $app->escape($text));
    }

    public function escapeProvider()
    {
        return array(
            array('&lt;', '<'),
            array('&gt;', '>'),
            array('&quot;', '"'),
            array("'", "'"),
            array('abc', 'abc'),
        );
    }
}
