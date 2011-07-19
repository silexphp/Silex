<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Extension;

use Silex\Application;
use Silex\Extension\ErrorExtension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorHandlerExceptionNoDebug()
    {
        $app = new Application();
        $app->register(new ErrorExtension());

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
        $app->register(new ErrorExtension());
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
        $app->register(new ErrorExtension());

        $request = Request::create('/foo');
        $response = $app->handle($request);
        $this->assertContains('<title>Sorry, the page you are looking for could not be found.</title>', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testErrorHandlerNotFoundDebug()
    {
        $app = new Application();
        $app->register(new ErrorExtension());
        $app['debug'] = true;

        $request = Request::create('/foo');
        $response = $app->handle($request);
        $this->assertContains('<title>No route found for "GET /foo" (500 Internal Server Error)</title>', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }
}
