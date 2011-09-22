<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use Monolog\Handler\TestHandler;

use Silex\Application;
use Silex\Provider\MonologServiceProvider;

use Symfony\Component\HttpFoundation\Request;

/**
 * MonologProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class MonologServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/monolog/src')) {
            $this->markTestSkipped('Monolog submodule was not installed.');
        }
    }

    public function testRegisterAndRender()
    {
        $app = new Application();

        $app->register(new MonologServiceProvider(), array(
            'monolog.class_path'    => __DIR__.'/../../../../vendor/monolog/src',
        ));

        $app['monolog.handler'] = $app->share(function () use ($app) {
            return new TestHandler($app['monolog.level']);
        });

        $app->get('/log', function () use ($app) {
            $app['monolog']->addDebug('logging a message');
        });

        $app->get('/error', function () {
            throw new \RuntimeException('very bad error');
        });

        $app->error(function (\Exception $e) {
            return 'error handled';
        });

        $this->assertFalse($app['monolog.handler']->hasDebugRecords());
        $this->assertFalse($app['monolog.handler']->hasErrorRecords());

        $request = Request::create('/log');
        $app->handle($request);

        $request = Request::create('/error');
        $app->handle($request);

        $this->assertTrue($app['monolog.handler']->hasDebugRecords());
        $this->assertTrue($app['monolog.handler']->hasErrorRecords());
    }
}
