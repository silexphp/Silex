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
    public function testSetRouteName()
    {
        $controller = new Controller(new Route('/foo'));
        $controller->setRouteName('foo');
        $this->assertEquals('foo', $controller->getRouteName());
    }

    /**
    * @expectedException Silex\Exception\ControllerFrozenException
    */
    public function testFrozenControllerShouldThrowException()
    {
        $controller = new Controller(new Route('/foo'));
        $controller->setRouteName('foo');
        $controller->freeze();
        $controller->setRouteName('bar');
    }
}
