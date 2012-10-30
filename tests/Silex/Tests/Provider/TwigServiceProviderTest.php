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

use Silex\Application;
use Silex\Provider\TwigServiceProvider;

use Symfony\Component\HttpFoundation\Request;

/**
 * TwigProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class TwigServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/twig/twig/lib')) {
            $this->markTestSkipped('Twig dependency was not installed.');
        }
    }

    public function testRegisterAndRender()
    {
        $app = new Application();

        $app->register(new TwigServiceProvider(), array(
            'twig.templates'    => array('hello' => 'Hello {{ name }}!'),
        ));

        $app->get('/hello/{name}', function ($name) use ($app) {
            return $app['twig']->render('hello', array('name' => $name));
        });

        $request = Request::create('/hello/john');
        $response = $app->handle($request);
        $this->assertEquals('Hello john!', $response->getContent());
    }

    public function testRenderFunction()
    {
        $app = $this->createRenderApp();

        $request = Request::create('/hello');
        $response = $app->handle($request);
        $this->assertEquals('foo', $response->getContent());
    }

    /** @test */
    public function renderShouldPrependBaseUrlToSubRequest()
    {
        $app = $this->createRenderApp();

        $server = array(
            'SCRIPT_FILENAME'   => '/Users/igor/Sites/localhost/foo/app.php',
            'REQUEST_URI'       => '/foo/app.php/hello',
            'SCRIPT_NAME'       => '/foo/app.php',
            'PATH_INFO'         => '/hello',
            'PHP_SELF'          => '/foo/app.php/hello',
        );
        $request = Request::create('/foo/app.php/hello', 'get', array(), array(), array(), $server);
        $response = $app->handle($request);
        $this->assertEquals('foo', $response->getContent());
    }

    public function testRenderRouteFunction()
    {
        $app = new Application();

        $app->register(new TwigServiceProvider(), array(
            'twig.templates'    => array(
                'hello' => '{{ render_route("foo", { bar: "a", baz: "b" }) }}',
            ),
        ));

        $app->get('/hello', function () use ($app) {
            return $app['twig']->render('hello');
        });

        $app->get('/le-foo/{bar}/{baz}', function ($bar, $baz) {
            return "le-foo/$bar/$baz";
        })
        ->bind('foo');

        $request = Request::create('/hello');
        $response = $app->handle($request);
        $this->assertEquals('le-foo/a/b', $response->getContent());
    }

    private function createRenderApp()
    {
        $app = new Application();

        $app->register(new TwigServiceProvider(), array(
            'twig.templates'    => array(
                'hello' => '{{ render("/foo") }}',
                'foo'   => 'foo',
            ),
        ));

        $app->get('/hello', function () use ($app) {
            return $app['twig']->render('hello');
        });

        $app->get('/foo', function () use ($app) {
            return $app['twig']->render('foo');
        });

        $app->error(function ($e) {
            throw $e;
        });

        return $app;
    }
}
