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

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Kernel;

/**
 * MonologProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class MonologServiceProviderTest extends TestCase
{
    private $currErrorHandler;

    protected function setUp()
    {
        $this->currErrorHandler = set_error_handler('var_dump');
        restore_error_handler();
    }

    protected function tearDown()
    {
        set_error_handler($this->currErrorHandler);
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

        $this->assertTrue($app['monolog.handler']->hasDebug('> GET /foo'));
        $this->assertTrue($app['monolog.handler']->hasDebug('< 200'));

        $records = $app['monolog.handler']->getRecords();
        if (Kernel::VERSION_ID < 30100) {
            $this->assertContains('Matched route "GET_foo"', $records[0]['message']);
        } else {
            $this->assertContains('Matched route "{route}".', $records[0]['message']);
            $this->assertSame('GET_foo', $records[0]['context']['route']);
        }
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

    public function testOverrideFormatter()
    {
        $app = new Application();

        $app->register(new MonologServiceProvider(), array(
            'monolog.formatter' => new JsonFormatter(),
            'monolog.logfile' => 'php://memory',
        ));

        $this->assertInstanceOf('Monolog\Formatter\JsonFormatter', $app['monolog.handler']->getFormatter());
    }

    public function testErrorLogging()
    {
        $app = $this->getApplication();

        $app->error(function (\Exception $e) {
            return 'error handled';
        });

        /*
         * Simulate 404, logged to error level
         */
        $this->assertFalse($app['monolog.handler']->hasErrorRecords());

        $request = Request::create('/error');
        $app->handle($request);

        $pattern = "#Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\NotFoundHttpException: No route found for \"GET /error\" \(uncaught exception\) at .* line \d+#";
        $this->assertMatchingRecord($pattern, Logger::ERROR, $app['monolog.handler']);

        /*
         * Simulate unhandled exception, logged to critical
         */
        $app->get('/error', function () {
            throw new \RuntimeException('very bad error');
        });

        $this->assertFalse($app['monolog.handler']->hasCriticalRecords());

        $request = Request::create('/error');
        $app->handle($request);

        $pattern = "#RuntimeException: very bad error \(uncaught exception\) at .* line \d+#";
        $this->assertMatchingRecord($pattern, Logger::CRITICAL, $app['monolog.handler']);
    }

    public function testRedirectLogging()
    {
        $app = $this->getApplication();

        $app->get('/foo', function () use ($app) {
            return new RedirectResponse('/bar', 302);
        });

        $this->assertFalse($app['monolog.handler']->hasInfoRecords());

        $request = Request::create('/foo');
        $app->handle($request);

        $this->assertTrue($app['monolog.handler']->hasDebug('< 302 /bar'));
    }

    public function testErrorLoggingGivesWayToSecurityExceptionHandling()
    {
        $app = $this->getApplication();
        $app['monolog.level'] = Logger::ERROR;

        $app->register(new \Silex\Provider\SecurityServiceProvider(), array(
            'security.firewalls' => array(
                'admin' => array(
                    'pattern' => '^/admin',
                    'http' => true,
                    'users' => array(),
                ),
            ),
        ));

        $app->get('/admin', function () {
            return 'SECURE!';
        });

        $request = Request::create('/admin');
        $app->run($request);

        $this->assertEmpty($app['monolog.handler']->getRecords(), 'Expected no logging to occur');
    }

    public function testStringErrorLevel()
    {
        $app = $this->getApplication();
        $app['monolog.level'] = 'info';

        $this->assertSame(Logger::INFO, $app['monolog.handler']->getLevel());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Provided logging level 'foo' does not exist. Must be a valid monolog logging level.
     */
    public function testNonExistentStringErrorLevel()
    {
        $app = $this->getApplication();
        $app['monolog.level'] = 'foo';

        $app['monolog.handler']->getLevel();
    }

    public function testDisableListener()
    {
        $app = $this->getApplication();
        unset($app['monolog.listener']);

        $app->handle(Request::create('/404'));

        $this->assertEmpty($app['monolog.handler']->getRecords(), 'Expected no logging to occur');
    }

    public function testExceptionFiltering()
    {
        $app = new Application();
        $app->get('/foo', function () use ($app) {
            throw new NotFoundHttpException();
        });

        $level = Logger::ERROR;
        $app->register(new MonologServiceProvider(), array(
            'monolog.exception.logger_filter' => $app->protect(function () {
                return Logger::DEBUG;
            }),
            'monolog.handler' => function () use ($app) {
                return new TestHandler($app['monolog.level']);
            },
            'monolog.level' => $level,
            'monolog.logfile' => 'php://memory',
        ));

        $request = Request::create('/foo');
        $app->handle($request);

        $this->assertCount(0, $app['monolog.handler']->getRecords(), 'Expected no logging to occur');
    }

    protected function assertMatchingRecord($pattern, $level, TestHandler $handler)
    {
        $found = false;
        $records = $handler->getRecords();
        foreach ($records as $record) {
            if (preg_match($pattern, $record['message']) && $record['level'] == $level) {
                $found = true;
                continue;
            }
        }
        $this->assertTrue($found, "Trying to find record matching $pattern with level $level");
    }

    protected function getApplication()
    {
        $app = new Application();

        $app->register(new MonologServiceProvider(), array(
            'monolog.handler' => function () use ($app) {
                $level = MonologServiceProvider::translateLevel($app['monolog.level']);

                return new TestHandler($level);
            },
            'monolog.logfile' => 'php://memory',
        ));

        return $app;
    }
}
