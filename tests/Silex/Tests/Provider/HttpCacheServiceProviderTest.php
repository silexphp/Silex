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

use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\Provider\HttpCacheServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HttpCacheProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class HttpCacheServiceProviderTest extends TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $app->register(new HttpCacheServiceProvider(), array(
            'http_cache.cache_dir' => sys_get_temp_dir().'/silex_http_cache_'.uniqid(),
        ));

        $this->assertInstanceOf('Silex\Provider\HttpCache\HttpCache', $app['http_cache']);

        return $app;
    }

    /**
     * @depends testRegister
     */
    public function testRunCallsShutdown($app)
    {
        $finished = false;

        $app->finish(function () use (&$finished) {
            $finished = true;
        });

        $app->get('/', function () use ($app) {
            return new UnsendableResponse('will do something after finish');
        });

        $request = Request::create('/');
        $app['http_cache']->run($request);

        $this->assertTrue($finished);
    }

    public function testDebugDefaultsToThatOfApp()
    {
        $app = new Application();

        $app->register(new HttpCacheServiceProvider(), array(
            'http_cache.cache_dir' => sys_get_temp_dir().'/silex_http_cache_'.uniqid(),
        ));

        $app['debug'] = true;
        $app['http_cache'];
        $this->assertTrue($app['http_cache.options']['debug']);
    }
}

class UnsendableResponse extends Response
{
    public function send()
    {
        // do nothing
    }
}
