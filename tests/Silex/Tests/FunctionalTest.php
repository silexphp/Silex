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
    public function testSetRouteName()
    {
        $application = new Application();

        $application->get('/', function() {
            return 'hello';
        })
        ->setRouteName('homepage');

        $application->get('/foo', function() {
            return 'foo';
        })
        ->setRouteName('foo_abc');

        $application->getControllerCollection()->flush();
        $routeCollection = $application->getRouteCollection();
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $routeCollection->get('homepage'));
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $routeCollection->get('foo_abc'));
    }
}
