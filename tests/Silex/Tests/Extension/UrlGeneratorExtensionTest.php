<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Extension;

use Silex\Application;
use Silex\Extension\UrlGeneratorExtension;

use Symfony\Component\HttpFoundation\Request;

/**
 * UrlGeneratorExtension test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class UrlGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $app->register(new UrlGeneratorExtension());

        $app->get('/hello/{name}', function ($name) {})
            ->bind('hello');

        $app['request'] = Request::create('/');
        $app['request_context'] = $app['request_context.factory']->create($app['request']);

        $this->assertInstanceOf('Symfony\Component\Routing\Generator\UrlGenerator', $app['url_generator']);
    }

    public function testUrlGeneration()
    {
        $app = new Application();

        $app->register(new UrlGeneratorExtension());

        $app->get('/hello/{name}', function ($name) {})
            ->bind('hello');

        $app['request'] = Request::create('/');
        $app['request_context'] = $app['request_context.factory']->create($app['request']);

        $url = $app['url_generator']->generate('hello', array('name' => 'john'));
        $this->assertEquals('/hello/john', $url);
    }

    public function testAbsoluteUrlGeneration()
    {
        $app = new Application();

        $app->register(new UrlGeneratorExtension());

        $app->get('/hello/{name}', function ($name) {})
            ->bind('hello');

        $app['request'] = Request::create('https://localhost:81/');
        $app['request_context'] = $app['request_context.factory']->create($app['request']);

        $url = $app['url_generator']->generate('hello', array('name' => 'john'), true);
        $this->assertEquals('https://localhost:81/hello/john', $url);
    }

    public function testUrlGenerationWithHttp()
    {
        $app = new Application();

        $app->register(new UrlGeneratorExtension());

        $app->get('/insecure', function () {})
            ->bind('insecure_page')
            ->requireHttp();

        $app['request'] = Request::create('https://localhost/');
        $app['request_context'] = $app['request_context.factory']->create($app['request']);

        $url = $app['url_generator']->generate('insecure_page');
        $this->assertEquals('http://localhost/insecure', $url);
    }

    public function testUrlGenerationWithHttps()
    {
        $app = new Application();

        $app->register(new UrlGeneratorExtension());

        $app->get('/secure', function () {})
            ->bind('secure_page')
            ->requireHttps();

        $app['request'] = Request::create('http://localhost/');
        $app['request_context'] = $app['request_context.factory']->create($app['request']);

        $url = $app['url_generator']->generate('secure_page');
        $this->assertEquals('https://localhost/secure', $url);
    }
}
