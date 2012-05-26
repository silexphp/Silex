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
        if (!is_dir(__DIR__.'/../../../../vendor/monolog/monolog/src')) {
            $this->markTestSkipped('Monolog dependency was not installed.');
        }
    }

    public function testRequestLogging()
    {
        $app = $this->getApplication();

        $app->get('/foo', function () use ($app) {
            return 'foo';
        });

        $this->assertFalse($app['monolog.handler']->hasInfoRecords());

        $request = Request::create('/foo');
        $app->handle($request);

        $this->assertTrue($app['monolog.handler']->hasInfo('> GET /foo'));
        $this->assertTrue($app['monolog.handler']->hasInfo('< 200'));
        $this->assertTrue($app['monolog.handler']->hasInfo('Matched route "GET_foo" (parameters: "_controller": "{}", "_route": "GET_foo")'));
    }

    public function testManualLogging()
    {
        $app = $this->getApplication();

        $app->get('/log', function () use ($app) {
            $app['monolog']->addDebug('logging a message');
        });

        $this->assertFalse($app['monolog.handler']->hasDebugRecords());

        $request = Request::create('/log');
        $app->handle($request);

        $this->assertTrue($app['monolog.handler']->hasDebug('logging a message'));
    }

    public function testErrorLogging()
    {
        $app = $this->getApplication();

        $app->get('/error', function () {
            throw new \RuntimeException('very bad error');
        });

        $app->error(function (\Exception $e) {
            return 'error handled';
        });

        $this->assertFalse($app['monolog.handler']->hasErrorRecords());

        $request = Request::create('/error');
        $app->handle($request);

        $this->assertTrue($app['monolog.handler']->hasError('very bad error'));
    }

    protected function getApplication()
    {
        $app = new Application();

        $app->register(new MonologServiceProvider());

        $app['monolog.handler'] = $app->share(function () use ($app) {
            return new TestHandler($app['monolog.level']);
        });

        return $app;
    }
}
