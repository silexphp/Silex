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

/**
 * Error handler test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorHandlerExceptionNoDebug()
    {
        $app = new Application();
        $app['debug'] = false;

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        $request = Request::create('/foo');
        $response = $app->handle($request);
        $this->assertContains('<title>Whoops, looks like something went wrong.</title>', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testErrorHandlerExceptionDebug()
    {
        $app = new Application();
        $app['debug'] = true;

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        $request = Request::create('/foo');
        $response = $app->handle($request);
        $this->assertContains('<title>foo exception (500 Internal Server Error)</title>', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testErrorHandlerNotFoundNoDebug()
    {
        $app = new Application();
        $app['debug'] = false;

        $request = Request::create('/foo');
        $response = $app->handle($request);
        $this->assertContains('<title>Sorry, the page you are looking for could not be found.</title>', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testErrorHandlerNotFoundDebug()
    {
        $app = new Application();
        $app['debug'] = true;

        $request = Request::create('/foo');
        $response = $app->handle($request);
        $this->assertContains('<title>No route found for "GET /foo" (500 Internal Server Error)</title>', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testNoErrorHandler()
    {
        $app = new Application();
        unset($app['exception_handler']);

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        try {
            $request = Request::create('/foo');
            $app->handle($request);
            $this->fail('->handle() should not catch exceptions where no error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }
    }

    public function testOneErrorHandler()
    {
        $app = new Application();

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        $app->error(function ($e) {
            return new Response('foo exception handler');
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($app, '/foo', 'foo exception handler');
    }

    public function testMultipleErrorHandlers()
    {
        $app = new Application();

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        $errors = 0;

        $app->error(function ($e) use (&$errors) {
            $errors++;
        });

        $app->error(function ($e) use (&$errors) {
            $errors++;
        });

        $app->error(function ($e) use (&$errors) {
            $errors++;
            return new Response('foo exception handler');
        });

        $app->error(function ($e) use (&$errors) {
            // should not execute
            $errors++;
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($app, '/foo', 'foo exception handler', 'should return the first response returned by an exception handler');

        $this->assertEquals(3, $errors, 'should execute error handlers until a response is returned');
    }

    public function testNoResponseErrorHandler()
    {
        $app = new Application();
        unset($app['exception_handler']);

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        $errors = 0;

        $app->error(function ($e) use (&$errors) {
            $errors++;
        });

        try {
            $request = Request::create('/foo');
            $app->handle($request);
            $this->fail('->handle() should not catch exceptions where an empty error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }

        $this->assertEquals(1, $errors, 'should execute the error handler');
    }

    public function testStringResponseErrorHandler()
    {
        $app = new Application();

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        $app->error(function ($e) {
            return 'foo exception handler';
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($app, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
    }

    public function testErrorHandlerException()
    {
        $app = new Application();

        $app->match('/foo', function () {
            throw new \RuntimeException('foo exception');
        });

        $app->error(function ($e) {
            throw new \RuntimeException('foo exception handler exception');
        });

        try {
            $request = Request::create('/foo');
            $this->checkRouteResponse($app, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
            $this->fail('->handle() should not catch exceptions thrown from an error handler');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception handler exception', $e->getMessage());
        }
    }

    protected function checkRouteResponse($app, $path, $expectedContent, $method = 'get', $message = null)
    {
        $request = Request::create($path, $method);
        $response = $app->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }
}
