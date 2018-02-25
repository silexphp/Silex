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

use Fig\Link\Link;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\Provider\CsrfServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\AssetServiceProvider;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * TwigProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class TwigServiceProviderTest extends TestCase
{
    public function testRegisterAndRender()
    {
        $app = new Application();

        $app->register(new TwigServiceProvider(), [
            'twig.templates' => ['hello' => 'Hello {{ name }}!'],
        ]);

        $app->get('/hello/{name}', function ($name) use ($app) {
            return $app['twig']->render('hello', ['name' => $name]);
        });

        $request = Request::create('/hello/john');
        $response = $app->handle($request);
        $this->assertEquals('Hello john!', $response->getContent());
    }

    public function testLoaderPriority()
    {
        $app = new Application();
        $app->register(new TwigServiceProvider(), [
            'twig.templates' => ['foo' => 'foo'],
        ]);
        $loader = $this->getMockBuilder('\Twig_LoaderInterface')->getMock();
        if (method_exists('\Twig_LoaderInterface', 'getSourceContext')) {
            $loader->expects($this->never())->method('getSourceContext');
        }
        $app['twig.loader.filesystem'] = function ($app) use ($loader) {
            return $loader;
        };
        $this->assertEquals('foo', $app['twig.loader']->getSourceContext('foo')->getCode());
    }

    public function testHttpFoundationIntegration()
    {
        $app = new Application();
        $app['request_stack']->push(Request::create('/dir1/dir2/file'));
        $app->register(new TwigServiceProvider(), [
            'twig.templates' => [
                'absolute' => '{{ absolute_url("foo.css") }}',
                'relative' => '{{ relative_path("/dir1/foo.css") }}',
            ],
        ]);

        $this->assertEquals('http://localhost/dir1/dir2/foo.css', $app['twig']->render('absolute'));
        $this->assertEquals('../foo.css', $app['twig']->render('relative'));
    }

    public function testAssetIntegration()
    {
        $app = new Application();
        $app->register(new TwigServiceProvider(), [
            'twig.templates' => ['hello' => '{{ asset("/foo.css") }}'],
        ]);
        $app->register(new AssetServiceProvider(), [
            'assets.version' => 1,
        ]);

        $this->assertEquals('/foo.css?1', $app['twig']->render('hello'));
    }

    public function testGlobalVariable()
    {
        $app = new Application();
        $app['request_stack']->push(Request::create('/?name=Fabien'));

        $app->register(new TwigServiceProvider(), [
            'twig.templates' => ['hello' => '{{ global.request.get("name") }}'],
        ]);

        $this->assertEquals('Fabien', $app['twig']->render('hello'));
    }

    public function testFormFactory()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $app->register(new CsrfServiceProvider());
        $app->register(new TwigServiceProvider());

        $this->assertInstanceOf('Twig_Environment', $app['twig']);
        $this->assertInstanceOf('Symfony\Bridge\Twig\Form\TwigRendererEngine', $app['twig.form.engine']);
        if (Kernel::VERSION_ID < 30400) {
            $this->assertInstanceOf('Symfony\Bridge\Twig\Form\TwigRenderer', $app['twig.form.renderer']);
        } else {
            $this->assertInstanceOf('Symfony\Component\Form\FormRenderer', $app['twig.form.renderer']);
        }
    }

    public function testFormWithoutCsrf()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $app->register(new TwigServiceProvider());

        $this->assertInstanceOf('Twig_Environment', $app['twig']);
    }

    public function testFormatParameters()
    {
        $app = new Application();

        $timezone = new \DateTimeZone('Europe/Paris');

        $app->register(new TwigServiceProvider(), [
            'twig.date.format' => 'Y-m-d',
            'twig.date.interval_format' => '%h hours',
            'twig.date.timezone' => $timezone,
            'twig.number_format.decimals' => 2,
            'twig.number_format.decimal_point' => ',',
            'twig.number_format.thousands_separator' => ' ',
        ]);

        $twig = $app['twig'];

        $this->assertSame(['Y-m-d', '%h hours'], $twig->getExtension('Twig_Extension_Core')->getDateFormat());
        $this->assertSame($timezone, $twig->getExtension('Twig_Extension_Core')->getTimezone());
        $this->assertSame([2, ',', ' '], $twig->getExtension('Twig_Extension_Core')->getNumberFormat());
    }

    public function testWebLinkIntegration()
    {
        if (!class_exists(HttpHeaderSerializer::class) || !class_exists(WebLinkExtension::class)) {
            $this->markTestSkipped('Twig WebLink extension not available.');
        }

        $app = new Application();
        $app['request_stack']->push($request = Request::create('/'));
        $app->register(new TwigServiceProvider(), [
            'twig.templates' => [
                'preload' => '{{ preload("/foo.css") }}',
            ],
        ]);

        $this->assertEquals('/foo.css', $app['twig']->render('preload'));

        $link = new Link('preload', '/foo.css');
        $this->assertEquals([$link], array_values($request->attributes->get('_links')->getLinks()));
    }
}
