<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Application;

use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

/**
 * MonologTrait test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MonologTraitTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->markTestSkipped('PHP 5.4 is required for this test');
        }

        if (!is_dir(__DIR__.'/../../../../vendor/monolog/monolog/src')) {
            $this->markTestSkipped('Monolog dependency was not installed.');
        }
    }

    public function testLog()
    {
        $app = $this->createApplication();

        $app->log('Foo');
        $app->log('Bar', array(), Logger::DEBUG);
        $this->assertTrue($app['monolog.handler']->hasInfo('Foo'));
        $this->assertTrue($app['monolog.handler']->hasDebug('Bar'));
    }

    public function createApplication()
    {
        $app = new MonologApplication();
        $app->register(new MonologServiceProvider(), array(
            'monolog.handler' => $app->share(function () use ($app) {
                return new TestHandler($app['monolog.level']);
            }),
        ));

        return $app;
    }
}
