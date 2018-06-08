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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class MiddlewareTest extends TestCase
{
    public function testBeforeAndAfterFilter()
    {
        $i = 0;
        $test = $this;

        $app = new Application();

        $app->before(function () use (&$i, $test) {
            $test->assertEquals(0, $i);
            ++$i;
        });

        $app->match('/foo', function () use (&$i, $test) {
            $test->assertEquals(1, $i);
            ++$i;
        });

        $app->after(function () use (&$i, $test) {
            $test->assertEquals(2, $i);
            ++$i;
        });

        $request = Request::create('/foo');
        $app->handle($request);

        $this->assertEquals(3, $i);
    }

    public function testAfterFilterWithResponseObject()
    {
        $i = 0;

        $app = new Application();

        $app->match('/foo', function () use (&$i) {
            ++$i;

            return new Response('foo');
        });

        $app->after(function () use (&$i) {
            ++$i;
        });

        $request = Request::create('/foo');
        $app->handle($request);

        $this->assertEquals(2, $i);
    }

    public function testMultipleFilters()
    {
        $i = 0;
        $test = $this;

        $app = new Application();

        $app->before(function () use (&$i, $test) {
            $test->assertEquals(0, $i);
            ++$i;
        });

        $app->before(function () use (&$i, $test) {
            $test->assertEquals(1, $i);
            ++$i;
        });

        $app->match('/foo', function () use (&$i, $test) {
            $test->assertEquals(2, $i);
            ++$i;
        });

        $app->after(function () use (&$i, $test) {
            $test->assertEquals(3, $i);
            ++$i;
        });

        $app->after(function () use (&$i, $test) {
            $test->assertEquals(4, $i);
            ++$i;
        });

        $request = Request::create('/foo');
        $app->handle($request);

        $this->assertEquals(5, $i);
    }

    public function testFiltersShouldFireOnException()
    {
        $i = 0;

        $app = new Application();

        $app->before(function () use (&$i) {
            ++$i;
        });

        $app->match('/foo', function () {
            throw new \RuntimeException();
        });

        $app->after(function () use (&$i) {
            ++$i;
        });

        $app->error(function () {
            return 'error handled';
        });

        $request = Request::create('/foo');
        $app->handle($request);

        $this->assertEquals(2, $i);
    }

    public function testFiltersShouldFireOnHttpException()
    {
        $i = 0;

        $app = new Application();

        $app->before(function () use (&$i) {
            ++$i;
        }, Application::EARLY_EVENT);

        $app->after(function () use (&$i) {
            ++$i;
        });

        $app->error(function () {
            return 'error handled';
        });

        $request = Request::create('/nowhere');
        $app->handle($request);

        $this->assertEquals(2, $i);
    }

    public function testBeforeFilterPreventsBeforeMiddlewaresToBeExecuted()
    {
        $app = new Application();

        $app->before(function () { return new Response('app before'); });

        $app->get('/', function () {
            return new Response('test');
        })->before(function () {
            return new Response('middleware before');
        });

        $this->assertEquals('app before', $app->handle(Request::create('/'))->getContent());
    }

    public function testBeforeFilterExceptionsWhenHandlingAnException()
    {
        $app = new Application();

        $app->before(function () { throw new \RuntimeException(''); });

        // even if the before filter throws an exception, we must have the 404
        $this->assertEquals(404, $app->handle(Request::create('/'))->getStatusCode());
    }

    public function testRequestShouldBePopulatedOnBefore()
    {
        $app = new Application();

        $app->before(function (Request $request) use ($app) {
            $app['project'] = $request->get('project');
        });

        $app->match('/foo/{project}', function () use ($app) {
            return $app['project'];
        });

        $request = Request::create('/foo/bar');
        $this->assertEquals('bar', $app->handle($request)->getContent());

        $request = Request::create('/foo/baz');
        $this->assertEquals('baz', $app->handle($request)->getContent());
    }

    public function testBeforeFilterAccessesRequestAndCanReturnResponse()
    {
        $app = new Application();

        $app->before(function (Request $request) {
            return new Response($request->get('name'));
        });

        $app->match('/', function () use ($app) { throw new \Exception('Should never be executed'); });

        $request = Request::create('/?name=Fabien');
        $this->assertEquals('Fabien', $app->handle($request)->getContent());
    }

    public function testBeforeCustomOrderArgumentsOnRoute()
    {
        $app = new Application();
        
        // Swap order of before() callback arguments based on type hints
        $app->match('/', function() use ($app) { return ''; })
            ->before(function (Application $app, Request $r) {
            $app['success'] = true;
        });
            
        $request = Request::create('/');
        $app->handle($request);
        $this->assertTrue($app['success']);
    }
    
    public function testBeforeFunctionWithoutTypeHintsOnRoute()
    {
        $app = new Application();
        
        $test = $this;
        $i = 0;
        // Normal order of arguments, but without type hints.
        $app->match('/', function() use ($app) { return ''; })->before(function ($r, $a) use ($test, &$i){
            $test->assertInstanceOf(Application::class, $a);
            $test->assertInstanceOf(Request::class, $r);
            $i++;
        });
            
        $request = Request::create('/');
        $app->handle($request);
        $this->assertEquals(1, $i);
    }
    
    public function testBeforeFunctionWithoutTypeHintsOnContainer() {
        $app = new Application();
        
        $test = $this;
        $i = 0;
        
        // Normal order of arguments, but without type hints.
        $app->before(function ($r, $a) use ($test, &$i){
            $test->assertInstanceOf(Application::class, $a);
            $test->assertInstanceOf(Request::class, $r);
            $i++;
        });
        $app->match('/', function() use ($app) { return ''; });
            
        $request = Request::create('/');
        $app->handle($request);
        $this->assertEquals(1, $i);
    }
    
    public function testBeforeWithRequestAttributesOnRoute()
    {
        $app = new Application();
        
        $test = $this;
        $i = 0;
        
        $app->match('/{attr}', function() use ($app) { return ''; })
            ->before(function (Application $app, Request $request, $attr) use ($test, &$i) {
                $test->assertInstanceOf(Application::class, $app);
                $test->assertInstanceOf(Request::class, $request);
                $test->assertEquals('attributeValue', $attr);
                $i++;
        });
            
        $request = Request::create('/attributeValue');
        $app->handle($request);
        $this->assertEquals(1, $i);
    }
    
    public function testBeforeCustomOrderWithRequestAttributesOnContainer()
    {
        $app = new Application();
        
        $test = $this;
        $i = 0;
        
        $app->before(function (Application $app, Request $request, $attr) use ($test, &$i) {
            $test->assertInstanceOf(Application::class, $app);
            $test->assertInstanceOf(Request::class, $request);
            $test->assertEquals('attributeValue', $attr);
            $i++;
        });
        
        $app->match('/{attr}', function() use ($app) { return ''; });
            
            
        $request = Request::create('/attributeValue');
        $app->handle($request);
        $this->assertEquals(1, $i);
    }

    public function testAfterFilterAccessRequestResponse()
    {
        $app = new Application();

        $app->after(function (Request $request, Response $response) {
            $response->setContent($response->getContent().'---');
        });

        $app->match('/', function () { return new Response('foo'); });

        $request = Request::create('/');
        $this->assertEquals('foo---', $app->handle($request)->getContent());
    }

    public function testAfterFilterCanReturnResponse()
    {
        $app = new Application();

        $app->after(function (Request $request, Response $response) {
            return new Response('bar');
        });

        $app->match('/', function () { return new Response('foo'); });

        $request = Request::create('/');
        $this->assertEquals('bar', $app->handle($request)->getContent());
    }

    public function testRouteAndApplicationMiddlewareParameterInjection()
    {
        $app = new Application();

        $test = $this;

        $middlewareTarget = [];
        $applicationBeforeMiddleware = function ($request, $app) use (&$middlewareTarget, $test) {
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Request', $request);
            $test->assertInstanceOf('\Silex\Application', $app);
            $middlewareTarget[] = 'application_before_middleware_triggered';
        };

        $applicationAfterMiddleware = function ($request, $response, $app) use (&$middlewareTarget, $test) {
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Request', $request);
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
            $test->assertInstanceOf('\Silex\Application', $app);
            $middlewareTarget[] = 'application_after_middleware_triggered';
        };

        $applicationFinishMiddleware = function ($request, $response, $app) use (&$middlewareTarget, $test) {
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Request', $request);
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
            $test->assertInstanceOf('\Silex\Application', $app);
            $middlewareTarget[] = 'application_finish_middleware_triggered';
        };

        $routeBeforeMiddleware = function ($request, $app) use (&$middlewareTarget, $test) {
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Request', $request);
            $test->assertInstanceOf('\Silex\Application', $app);
            $middlewareTarget[] = 'route_before_middleware_triggered';
        };

        $routeAfterMiddleware = function ($request, $response, $app) use (&$middlewareTarget, $test) {
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Request', $request);
            $test->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
            $test->assertInstanceOf('\Silex\Application', $app);
            $middlewareTarget[] = 'route_after_middleware_triggered';
        };

        $app->before($applicationBeforeMiddleware);
        $app->after($applicationAfterMiddleware);
        $app->finish($applicationFinishMiddleware);

        $app->match('/', function () {
            return new Response('foo');
        })
        ->before($routeBeforeMiddleware)
        ->after($routeAfterMiddleware);

        $request = Request::create('/');
        $response = $app->handle($request);
        $app->terminate($request, $response);

        $this->assertSame(['application_before_middleware_triggered', 'route_before_middleware_triggered', 'route_after_middleware_triggered', 'application_after_middleware_triggered', 'application_finish_middleware_triggered'], $middlewareTarget);
    }
}
