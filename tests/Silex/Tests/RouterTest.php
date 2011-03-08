<?php

namespace Silex\Tests;

use Silex\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Router test cases.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testMapRouting()
    {
        $framework = new Framework();

        $framework->match('/foo', function() {
            return 'foo';
        });

        $framework->match('/bar', function() {
            return 'bar';
        });

        $framework->match('/', function() {
            return 'root';
        });

        $this->checkRouteResponse($framework, '/foo', 'foo');
        $this->checkRouteResponse($framework, '/bar', 'bar');
        $this->checkRouteResponse($framework, '/', 'root');
    }

    public function testStatusCode()
    {
        $framework = new Framework();

        $framework->put('/created', function() {
            return new Response('', 201);
        });

        $framework->match('/forbidden', function() {
            return new Response('', 403);
        });

        $framework->match('/not_found', function() {
            return new Response('', 404);
        });

        $request = Request::create('/created', 'put');
        $response = $framework->handle($request);
        $this->assertEquals(201, $response->getStatusCode());

        $request = Request::create('/forbidden');
        $response = $framework->handle($request);
        $this->assertEquals(403, $response->getStatusCode());

        $request = Request::create('/not_found');
        $response = $framework->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRedirect()
    {
        $framework = new Framework();

        $framework->match('/redirect', function() {
            return new RedirectResponse('/target');
        });

        $request = Request::create('/redirect');
        $response = $framework->handle($request);
        $this->assertTrue($response->isRedirected('/target'));
    }

    /**
    * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
    */
    public function testMissingRoute()
    {
        $framework = new Framework();

        $request = Request::create('/baz');
        $framework->handle($request);
    }

    public function testMethodRouting()
    {
        $framework = new Framework();

        $framework->match('/foo', function() {
            return 'foo';
        });

        $framework->match('/bar', function() {
            return 'bar';
        }, 'GET|POST');

        $framework->get('/resource', function() {
            return 'get resource';
        });

        $framework->post('/resource', function() {
            return 'post resource';
        });

        $framework->put('/resource', function() {
            return 'put resource';
        });

        $framework->delete('/resource', function() {
            return 'delete resource';
        });

        $this->checkRouteResponse($framework, '/foo', 'foo');
        $this->checkRouteResponse($framework, '/bar', 'bar');
        $this->checkRouteResponse($framework, '/bar', 'bar', 'post');
        $this->checkRouteResponse($framework, '/resource', 'get resource');
        $this->checkRouteResponse($framework, '/resource', 'post resource', 'post');
        $this->checkRouteResponse($framework, '/resource', 'put resource', 'put');
        $this->checkRouteResponse($framework, '/resource', 'delete resource', 'delete');
    }

    protected function checkRouteResponse($framework, $path, $expectedContent, $method = 'get', $message = null)
    {
        $request = Request::create($path, $method);
        $response = $framework->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }

    public function testRequestShouldBeStoredRegardlessOfRouting() {
        $framework = new Framework();
        $framework->get('/foo', function() use ($framework) {
            return new Response($framework->getRequest()->getRequestUri());
        });
        $framework->error(function($e) use ($framework) {
            return new Response($framework->getRequest()->getRequestUri());
        });
        foreach(array('/foo', '/bar') as $path) {
          $request = Request::create($path);
          $response = $framework->handle($request);
          $this->assertContains($path, $response->getContent());
        }
    }
}
