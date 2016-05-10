<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Route;

use Silex\Route\EmptyPathRoute;

/**
 * @author RJ Garcia <rj@bighead.net>
 */
class EmptyPathRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $route = new EmptyPathRoute('');
        $this->assertEquals('', $route->getPath());
    }

    /**
     * @dataProvider pathProvider
     */
    public function testPath(EmptyPathRoute $route, $path, $expected)
    {
        $route->setPath($path);
        $this->assertEquals($expected, $route->getPath());
    }

    public function pathProvider()
    {
        $route = new EmptyPathRoute('/');

        return [
            [$route, '', ''],
            [$route, '/path', '/path'],
            [$route, '', ''],
            [$route, 'path', '/path'],
        ];
    }

    public function testSerialize()
    {
        $route = new EmptyPathRoute('', ['key' => 'val']);
        $new_route = new EmptyPathRoute('/path');
        $new_route->unserialize($route->serialize());

        $this->assertEquals('val', $new_route->getDefault('key'));
        $this->assertEquals('', $new_route->getPath());
    }
}
