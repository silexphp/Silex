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
    public function testNoErrorHandler()
    {
        $application = new Application();

        $application->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        try {
            $request = Request::create('/foo');
            $application->handle($request);
            $this->fail('->handle() should not catch exceptions where no error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }
    }

    public function testOneErrorHandler()
    {
        $application = new Application();

        $application->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $application->error(function($e) {
            return new Response('foo exception handler');
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($application, '/foo', 'foo exception handler');
    }

    public function testMultipleErrorHandlers()
    {
        $application = new Application();

        $application->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $errors = 0;

        $application->error(function($e) use (&$errors) {
            $errors++;
        });

        $application->error(function($e) use (&$errors) {
            $errors++;
        });

        $application->error(function($e) use (&$errors) {
            $errors++;
            return new Response('foo exception handler');
        });

        $application->error(function($e) use (&$errors) {
            // should not execute
            $errors++;
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($application, '/foo', 'foo exception handler', 'should return the first response returned by an exception handler');

        $this->assertEquals(3, $errors, 'should execute error handlers until a response is returned');
    }

    public function testNoResponseErrorHandler()
    {
        $application = new Application();

        $application->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $errors = 0;

        $application->error(function($e) use (&$errors) {
            $errors++;
        });

        try {
            $request = Request::create('/foo');
            $application->handle($request);
            $this->fail('->handle() should not catch exceptions where an empty error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }

        $this->assertEquals(1, $errors, 'should execute the error handler');
    }

    public function testStringResponseErrorHandler()
    {
        $application = new Application();

        $application->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $application->error(function($e) {
            return 'foo exception handler';
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($application, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
    }

    public function testErrorHandlerException()
    {
        $application = new Application();

        $application->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $application->error(function($e) {
            throw new \RuntimeException('foo exception handler exception');
        });

        try {
            $request = Request::create('/foo');
            $this->checkRouteResponse($application, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
            $this->fail('->handle() should not catch exceptions thrown from an error handler');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception handler exception', $e->getMessage());
        }
    }

    protected function checkRouteResponse($application, $path, $expectedContent, $method = 'get', $message = null)
    {
        $request = Request::create($path, $method);
        $response = $application->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }
}
