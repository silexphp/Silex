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

use Silex\ControllerResolver;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * ControllerResolver test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetArgumentsWithAppTypehint()
    {
        $app = new Application();
        $resolver = new ControllerResolver($app);

        $controller = function (Application $foo) {};

        $args = $resolver->getArguments(Request::create('/'), $controller);
        $this->assertSame($app, $args[0]);
    }

    public function testGetArgumentsWithAppName()
    {
        $app = new Application();
        $resolver = new ControllerResolver($app);

        $controller = function ($app) {};

        $args = $resolver->getArguments(Request::create('/'), $controller);
        $this->assertSame($app, $args[0]);
    }

    public function testGetArgumentsWithRequestName()
    {
        $app = new Application();
        $resolver = new ControllerResolver($app);

        $controller = function ($request) {};

        $request = Request::create('/');
        $args = $resolver->getArguments($request, $controller);
        $this->assertSame($request, $args[0]);
    }

    public function testGetArgumentsWithConflict()
    {
        $app = new Application();
        $resolver = new ControllerResolver($app);

        $controller = function ($request) {};

        $request = Request::create('/');
        $request->attributes->set('request', 'foo');
        $args = $resolver->getArguments($request, $controller);
        $this->assertSame('foo', $args[0]);
    }

    public function testGetArgumentsWithAlternateTypehint()
    {
        $app = new Application();
        $resolver = new ControllerResolver($app);

        $controller = function (Foo $request = null) {};

        $args = $resolver->getArguments(Request::create('/'), $controller);
        $this->assertSame(null, $args[0]);
    }
}

class Foo {}
