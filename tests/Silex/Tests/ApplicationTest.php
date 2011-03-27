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
        $application = new Application();

        $returnValue = $application->match('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $application->get('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $application->post('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $application->put('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $application->delete('/foo', function() {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);
    }

    public function testGetRequest()
    {
        $application = new Application();

        $application->get('/', function() {
            return 'root';
        });

        $request = Request::create('/');

        $application->handle($request);

        $this->assertEquals($request, $application['request']);
    }

    public function testgetRoutesWithNoRoutes()
    {
        $application = new Application();

        $routes = $application['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertEquals(0, count($routes->all()));
    }

    public function testgetRoutesWithRoutes()
    {
        $application = new Application();

        $application->get('/foo', function() {
            return 'foo';
        });

        $application->get('/bar', function() {
            return 'bar';
        });

        $routes = $application['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertEquals(0, count($routes->all()));
        $application->flush();
        $this->assertEquals(2, count($routes->all()));
    }
}
