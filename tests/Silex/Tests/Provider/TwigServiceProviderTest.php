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
use Silex\Provider\CsrfServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\AssetServiceProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * TwigProvider test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class TwigServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterAndRender()
    {
        $app = new Application();

        $app->register(new TwigServiceProvider(), array(
            'twig.templates' => array('hello' => 'Hello {{ name }}!'),
        ));

        $app->get('/hello/{name}', function ($name) use ($app) {
            return $app['twig']->render('hello', array('name' => $name));
        });

        $request = Request::create('/hello/john');
        $response = $app->handle($request);
        $this->assertEquals('Hello john!', $response->getContent());
    }

    public function testLoaderPriority()
    {
        $app = new Application();
        $app->register(new TwigServiceProvider(), array(
            'twig.templates' => array('foo' => 'foo'),
        ));
        $loader = $this->getMockBuilder('\Twig_LoaderInterface')->getMock();
        $loader->expects($this->never())->method('getSourceContext');
        $app['twig.loader.filesystem'] = function ($app) use ($loader) {
            return $loader;
        };
        $this->assertEquals('foo', $app['twig.loader']->getSourceContext('foo')->getCode());
    }

    public function testHttpFoundationIntegration()
    {
        $app = new Application();
        $app['request_stack']->push(Request::create('/dir1/dir2/file'));
        $app->register(new TwigServiceProvider(), array(
            'twig.templates' => array(
                'absolute' => '{{ absolute_url("foo.css") }}',
                'relative' => '{{ relative_path("/dir1/foo.css") }}',
            ),
        ));

        $this->assertEquals('http://localhost/dir1/dir2/foo.css', $app['twig']->render('absolute'));
        $this->assertEquals('../foo.css', $app['twig']->render('relative'));
    }

    public function testAssetIntegration()
    {
        $app = new Application();
        $app->register(new TwigServiceProvider(), array(
            'twig.templates' => array('hello' => '{{ asset("/foo.css") }}'),
        ));
        $app->register(new AssetServiceProvider(), array(
            'assets.version' => 1,
        ));

        $this->assertEquals('/foo.css?1', $app['twig']->render('hello'));
    }

    public function testGlobalVariable()
    {
        $app = new Application();
        $app['request_stack']->push(Request::create('/?name=Fabien'));

        $app->register(new TwigServiceProvider(), array(
            'twig.templates' => array('hello' => '{{ global.request.get("name") }}'),
        ));

        $this->assertEquals('Fabien', $app['twig']->render('hello'));
    }

    public function testFormFactory()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $app->register(new CsrfServiceProvider());
        $app->register(new TwigServiceProvider());

        $this->assertInstanceOf('Twig_Environment', $app['twig'], 'Service twig is created successful.');
        $this->assertInstanceOf('Symfony\Bridge\Twig\Form\TwigRendererEngine', $app['twig.form.engine'], 'Service twig.form.engine is created successful.');
        $this->assertInstanceOf('Symfony\Bridge\Twig\Form\TwigRenderer', $app['twig.form.renderer'], 'Service twig.form.renderer is created successful.');
    }

    public function testFormWithoutCsrf()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $app->register(new TwigServiceProvider());

        $this->assertInstanceOf('Twig_Environment', $app['twig']);
    }
}
