<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests;

use Silex\Framework;

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
        $framework = new Framework();

        $framework->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        try {
            $request = Request::create('/foo');
            $framework->handle($request);
            $this->fail('->handle() should not catch exceptions where no error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }
    }

    public function testOneErrorHandler()
    {
        $framework = new Framework();

        $framework->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $framework->error(function($e) {
            return new Response('foo exception handler');
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($framework, '/foo', 'foo exception handler');
    }

    public function testMultipleErrorHandlers()
    {
        $framework = new Framework();

        $framework->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $errors = 0;

        $framework->error(function($e) use (&$errors) {
            $errors++;
        });

        $framework->error(function($e) use (&$errors) {
            $errors++;
        });

        $framework->error(function($e) use (&$errors) {
            $errors++;
            return new Response('foo exception handler');
        });

        $framework->error(function($e) use (&$errors) {
            // should not execute
            $errors++;
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($framework, '/foo', 'foo exception handler', 'should return the first response returned by an exception handler');

        $this->assertEquals(3, $errors, 'should execute error handlers until a response is returned');
    }

    public function testNoResponseErrorHandler()
    {
        $framework = new Framework();

        $framework->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $errors = 0;

        $framework->error(function($e) use (&$errors) {
            $errors++;
        });

        try {
            $request = Request::create('/foo');
            $framework->handle($request);
            $this->fail('->handle() should not catch exceptions where an empty error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }

        $this->assertEquals(1, $errors, 'should execute the error handler');
    }

    public function testStringResponseErrorHandler()
    {
        $framework = new Framework();

        $framework->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $framework->error(function($e) {
            return 'foo exception handler';
        });

        $request = Request::create('/foo');
        $this->checkRouteResponse($framework, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
    }

    public function testErrorHandlerException()
    {
        $framework = new Framework();

        $framework->match('/foo', function() {
            throw new \RuntimeException('foo exception');
        });

        $framework->error(function($e) {
            throw new \RuntimeException('foo exception handler exception');
        });

        try {
            $request = Request::create('/foo');
            $this->checkRouteResponse($framework, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
            $this->fail('->handle() should not catch exceptions thrown from an error handler');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception handler exception', $e->getMessage());
        }
    }

    protected function checkRouteResponse($framework, $path, $expectedContent, $method = 'get', $message = null)
    {
        $request = Request::create($path, $method);
        $response = $framework->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }
}
