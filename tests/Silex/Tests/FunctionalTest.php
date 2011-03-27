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

/**
 * Controller test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    public function testBind()
    {
        $application = new Application();

        $application->get('/', function() {
            return 'hello';
        })
        ->bind('homepage');

        $application->get('/foo', function() {
            return 'foo';
        })
        ->bind('foo_abc');

        $application->flush();
        $routes = $application['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $routes->get('homepage'));
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $routes->get('foo_abc'));
    }
}
