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
use Silex\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testExtensions()
    {
        $route = new Route();
        $route->setExtensions(array('foo' => function (Route $route, $value) {
            $route->setOption('_foo', $value);
        }));

        $route->foo('foo');
        $this->assertEquals('foo', $route->getOption('_foo'));
    }
}
