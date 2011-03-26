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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Router test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testMapRouting()
    {
        $application = new Application();

        $application->match('/foo', function() {
            return 'foo';
        });

        $application->match('/bar', function() {
            return 'bar';
        });

        $application->match('/', function() {
            return 'root';
        });

        $this->checkRouteResponse($application, '/foo', 'foo');
        $this->checkRouteResponse($application, '/bar', 'bar');
        $this->checkRouteResponse($application, '/', 'root');
    }

    public function testStatusCode()
    {
        $application = new Application();

        $application->put('/created', function() {
            return new Response('', 201);
        });

        $application->match('/forbidden', function() {
            return new Response('', 403);
        });

        $application->match('/not_found', function() {
            return new Response('', 404);
        });

        $request = Request::create('/created', 'put');
        $response = $application->handle($request);
        $this->assertEquals(201, $response->getStatusCode());

        $request = Request::create('/forbidden');
        $response = $application->handle($request);
        $this->assertEquals(403, $response->getStatusCode());

        $request = Request::create('/not_found');
        $response = $application->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRedirect()
    {
        $application = new Application();

        $application->match('/redirect', function() {
            return new RedirectResponse('/target');
        });

        $request = Request::create('/redirect');
        $response = $application->handle($request);
        $this->assertTrue($response->isRedirected('/target'));
    }

    /**
    * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
    */
    public function testMissingRoute()
    {
        $application = new Application();

        $request = Request::create('/baz');
        $application->handle($request);
    }

    public function testMethodRouting()
    {
        $application = new Application();

        $application->match('/foo', function() {
            return 'foo';
        });

        $application->match('/bar', function() {
            return 'bar';
        }, 'GET|POST');

        $application->get('/resource', function() {
            return 'get resource';
        });

        $application->post('/resource', function() {
            return 'post resource';
        });

        $application->put('/resource', function() {
            return 'put resource';
        });

        $application->delete('/resource', function() {
            return 'delete resource';
        });

        $this->checkRouteResponse($application, '/foo', 'foo');
        $this->checkRouteResponse($application, '/bar', 'bar');
        $this->checkRouteResponse($application, '/bar', 'bar', 'post');
        $this->checkRouteResponse($application, '/resource', 'get resource');
        $this->checkRouteResponse($application, '/resource', 'post resource', 'post');
        $this->checkRouteResponse($application, '/resource', 'put resource', 'put');
        $this->checkRouteResponse($application, '/resource', 'delete resource', 'delete');
    }

    public function testRequestShouldBeStoredRegardlessOfRouting() {
        $application = new Application();
        $application->get('/foo', function() use ($application) {
            return new Response($application['request']->getRequestUri());
        });
        $application->error(function($e) use ($application) {
            return new Response($application['request']->getRequestUri());
        });
        foreach(array('/foo', '/bar') as $path) {
          $request = Request::create($path);
          $response = $application->handle($request);
          $this->assertContains($path, $response->getContent());
        }
    }

    protected function checkRouteResponse($application, $path, $expectedContent, $method = 'get', $message = null)
    {
        $request = Request::create($path, $method);
        $response = $application->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }
}
