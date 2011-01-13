<?php

namespace Silex\Tests;

use Silex\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $framework = Framework::create(array(
            '/foo' => function() {
                return 'foo';
            },
            '/bar' => function() {
                return 'bar';
            },
            '/' => function() {
                return 'root';
            },
        ));

        $this->checkRouteResponse($framework, '/foo', 'foo');
        $this->checkRouteResponse($framework, '/bar', 'bar');
        $this->checkRouteResponse($framework, '/', 'root');
    }

    public function testMapRoutingMethods()
    {
        $framework = Framework::create(array(
            'GET /foo' => function() {
                return 'foo';
            },
            'PUT|DELETE /bar' => function() {
                return 'bar';
            },
            '/' => function() {
                return 'root';
            },
        ));

        // foo route
        $this->checkRouteResponse($framework, '/foo', 'foo');

        // bar route
        $this->checkRouteResponse($framework, '/bar', 'bar', 'put');
        $this->checkRouteResponse($framework, '/bar', 'bar', 'delete');

        // root route
        $this->checkRouteResponse($framework, '/', 'root');
        $this->checkRouteResponse($framework, '/', 'root', 'post');
        $this->checkRouteResponse($framework, '/', 'root', 'put');
        $this->checkRouteResponse($framework, '/', 'root', 'delete');

        try {
            $request = Request::create('http://test.com/bar');
            $framework->handle($request);

            $this->fail('Framework must reject HTTP GET method to /bar');
        } catch (NotFoundHttpException $expected) {
        }

        try {
            $request = Request::create('http://test.com/bar', 'post');
            $framework->handle($request);

            $this->fail('Framework must reject HTTP POST method to /bar');
        } catch (NotFoundHttpException $expected) {
        }
    }

    public function testMapRoutingParameters()
    {
        $framework = Framework::create(array(
            '/hello' => function() {
                return "Hello anon";
            },
            '/hello/{name}' => function($name) {
                return "Hello $name";
            },
            '/goodbye/{name}' => function($name) {
                return "Goodbye $name";
            },
            '/tell/{name}/{message}' => function($message, $name) {
                return "Message for $name: $message";
            },
            '/' => function() {
                return 'root';
            },
        ));

        $this->checkRouteResponse($framework, '/hello', 'Hello anon');
        $this->checkRouteResponse($framework, '/hello/alice', 'Hello alice');
        $this->checkRouteResponse($framework, '/hello/bob', 'Hello bob');
        $this->checkRouteResponse($framework, '/goodbye/alice', 'Goodbye alice');
        $this->checkRouteResponse($framework, '/goodbye/bob', 'Goodbye bob');
        $this->checkRouteResponse($framework, '/tell/bob/secret', 'Message for bob: secret');
        $this->checkRouteResponse($framework, '/', 'root');
    }

    public function testStatusCode()
    {
        $framework = Framework::create(array(
            'PUT /created' => function() {
                return new Response('', 201);
            },
            '/forbidden' => function() {
                return new Response('', 403);
            },
            '/not_found' => function() {
                return new Response('', 404);
            },
        ));

        $request = Request::create('http://test.com/created', 'put');
        $response = $framework->handle($request);
        $this->assertEquals(201, $response->getStatusCode());

        $request = Request::create('http://test.com/forbidden');
        $response = $framework->handle($request);
        $this->assertEquals(403, $response->getStatusCode());

        $request = Request::create('http://test.com/not_found');
        $response = $framework->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRedirect()
    {
        $framework = Framework::create(array(
            '/redirect' => function() {
                $response = new Response();
                $response->setRedirect('/target');
                return $response;
            },
        ));

        $request = Request::create('http://test.com/redirect');
        $response = $framework->handle($request);
        $this->assertTrue($response->isRedirected('/target'));
    }

    /**
    * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
    */
    public function testMissingRoute()
    {
        $framework = Framework::create();

        $request = Request::create('http://test.com/baz');
        $framework->handle($request);
    }

    public function testMethodRouting()
    {
        $framework = Framework::create();
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
        $request = Request::create('http://test.com' . $path, $method);
        $response = $framework->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }
}
