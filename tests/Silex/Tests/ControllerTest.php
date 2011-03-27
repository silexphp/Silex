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

use Silex\Controller;
use Symfony\Component\Routing\Route;

/**
 * Controller test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testBind()
    {
        $controller = new Controller(new Route('/foo'));
        $controller->bind('foo');
        $this->assertEquals('foo', $controller->getRouteName());
    }

    /**
    * @expectedException Silex\Exception\ControllerFrozenException
    */
    public function testBindOnFrozenControllerShouldThrowException()
    {
        $controller = new Controller(new Route('/foo'));
        $controller->bind('foo');
        $controller->freeze();
        $controller->bind('bar');
    }
}
