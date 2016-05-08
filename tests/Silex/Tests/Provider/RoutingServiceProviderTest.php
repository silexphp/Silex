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

use Pimple\Container;
use Silex\Application;
use Silex\Provider\RoutingServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * RoutingProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class RoutingServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $app->get('/hello/{name}', function ($name) {})
            ->bind('hello');

        $app->get('/', function () {});

        $request = Request::create('/');
        $app->handle($request);

        $this->assertInstanceOf('Symfony\Component\Routing\Generator\UrlGenerator', $app['url_generator']);
    }

    public function testUrlGeneration()
    {
        $app = new Application();

        $app->get('/hello/{name}', function ($name) {})
            ->bind('hello');

        $app->get('/', function () use ($app) {
            return $app['url_generator']->generate('hello', array('name' => 'john'));
        });

        $request = Request::create('/');
        $response = $app->handle($request);

        $this->assertEquals('/hello/john', $response->getContent());
    }

    public function testAbsoluteUrlGeneration()
    {
        $app = new Application();

        $app->get('/hello/{name}', function ($name) {})
            ->bind('hello');

        $app->get('/', function () use ($app) {
            return $app['url_generator']->generate('hello', array('name' => 'john'), UrlGeneratorInterface::ABSOLUTE_URL);
        });

        $request = Request::create('https://localhost:81/');
        $response = $app->handle($request);

        $this->assertEquals('https://localhost:81/hello/john', $response->getContent());
    }

    public function testUrlGenerationWithHttp()
    {
        $app = new Application();

        $app->get('/insecure', function () {})
            ->bind('insecure_page')
            ->requireHttp();

        $app->get('/', function () use ($app) {
            return $app['url_generator']->generate('insecure_page');
        });

        $request = Request::create('https://localhost/');
        $response = $app->handle($request);

        $this->assertEquals('http://localhost/insecure', $response->getContent());
    }

    public function testUrlGenerationWithHttps()
    {
        $app = new Application();

        $app->get('/secure', function () {})
            ->bind('secure_page')
            ->requireHttps();

        $app->get('/', function () use ($app) {
            return $app['url_generator']->generate('secure_page');
        });

        $request = Request::create('http://localhost/');
        $response = $app->handle($request);

        $this->assertEquals('https://localhost/secure', $response->getContent());
    }

    public function testControllersFactory()
    {
        $app = new Container();
        $app->register(new RoutingServiceProvider());
        $coll = $app['controllers_factory'];
        $coll->mount('/blog', function ($blog) {
            $this->assertInstanceOf('Silex\ControllerCollection', $blog);
        });
    }
}
