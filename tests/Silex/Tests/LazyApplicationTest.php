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

use Silex\LazyApplication;

/**
 * Application test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class LazyApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadsApplication()
    {
        $app = new LazyApplication(__DIR__.'/../../fixtures/app.php');

        $this->assertInstanceOf('Silex\Application', $app->__invoke());
    }

    public function testConfigureIfConfiguratorAvailable()
    {
        $configurator = function($app) {};

        $app = new LazyApplication(__DIR__.'/../../fixtures/app.php', $configurator);
        $app = $app->__invoke();

        $this->assertTrue($app['silex.configured']);
    }

    public function testConfigureUsingConfigurator()
    {
        $configurator = function($app) { $app['configurator'] = true; };

        $app = new LazyApplication(__DIR__.'/../../fixtures/app.php', $configurator);
        $app = $app->__invoke();

        $this->assertTrue($app['configurator']);
    }

    public function testDontConfigureIfNoConfigurator() {
        $app = new LazyApplication(__DIR__.'/../../fixtures/app.php');
        $app = $app->__invoke();

        $this->assertFalse(isset($app['silex.configured']));
    }

    public function testDontConfigureIfAlreadyConfigured() {
        $configurator = function($app) {
            static $pass = 0;
            $app['configurator'] = $pass;
            $pass++;
        };

        $app = new LazyApplication(__DIR__.'/../../fixtures/app.php', $configurator);
        $app->__invoke();
        $app = $app->__invoke();

        $this->assertEquals(0, $app['configurator']);
    }
}
