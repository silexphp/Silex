<?php

namespace Silex\Tests;

use Silex\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Error handler test cases.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoErrorHandler()
    {
        $framework = Framework::create(array(
            '/foo' => function() {
                throw new \RuntimeException('foo exception');
            },
        ));

        try {
            $request = Request::create('http://test.com/foo');
            $framework->handle($request);
            $this->fail('->handle() should not catch exceptions where no error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }
    }

    public function testOneErrorHandler()
    {
        $framework = Framework::create(array(
            '/foo' => function() {
                throw new \RuntimeException('foo exception');
            },
        ));

        $framework->error(function($e) {
            return new Response('foo exception handler');
        });

        $request = Request::create('http://test.com/foo');
        $this->checkRouteResponse($framework, '/foo', 'foo exception handler');
    }

    public function testMultipleErrorHandlers()
    {
        $framework = Framework::create(array(
            '/foo' => function() {
                throw new \RuntimeException('foo exception');
            },
        ));

        $errors = 0;

        $framework->error(function($e) use (&$errors) {
            $errors++;
        });

        $framework->error(function($e) use (&$errors) {
            $errors++;
            return new Response('foo exception handler');
        });

        $framework->error(function($e) use (&$errors) {
            $errors++;
            return new Response('foo exception handler 2');
        });

        $request = Request::create('http://test.com/foo');
        $this->checkRouteResponse($framework, '/foo', 'foo exception handler', 'should return the first response returned by an exception handler');

        $this->assertEquals(3, $errors, 'should execute all error handlers');
    }

    public function testNoResponseErrorHandler()
    {
        $framework = Framework::create(array(
            '/foo' => function() {
                throw new \RuntimeException('foo exception');
            },
        ));

        $errors = 0;

        $framework->error(function($e) use (&$errors) {
            $errors++;
        });

        try {
            $request = Request::create('http://test.com/foo');
            $framework->handle($request);
            $this->fail('->handle() should not catch exceptions where an empty error handler was supplied');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception', $e->getMessage());
        }

        $this->assertEquals(1, $errors, 'should execute the error handler');
    }

    public function testStringResponseErrorHandler()
    {
        $framework = Framework::create(array(
            '/foo' => function() {
                throw new \RuntimeException('foo exception');
            },
        ));

        $framework->error(function($e) {
            return 'foo exception handler';
        });

        $request = Request::create('http://test.com/foo');
        $this->checkRouteResponse($framework, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
    }

    public function testErrorHandlerException()
    {
        $framework = Framework::create(array(
            '/foo' => function() {
                throw new \RuntimeException('foo exception');
            },
        ));

        $framework->error(function($e) {
            throw new \RuntimeException('foo exception handler exception');
        });

        try {
            $request = Request::create('http://test.com/foo');
            $this->checkRouteResponse($framework, '/foo', 'foo exception handler', 'should accept a string response from the error handler');
            $this->fail('->handle() should not catch exceptions thrown from an error handler');
        } catch (\RuntimeException $e) {
            $this->assertEquals('foo exception handler exception', $e->getMessage());
        }
    }

    protected function checkRouteResponse($framework, $path, $expectedContent, $method = 'get', $message = null)
    {
        $request = Request::create('http://test.com' . $path, $method);
        $response = $framework->handle($request);
        $this->assertEquals($expectedContent, $response->getContent(), $message);
    }
}
