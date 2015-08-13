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
use Silex\Provider\SwiftmailerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class SwiftmailerServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function defaultOptions()
    {
        return array(
            array(
                array(
                    'host' => 'justchanginghost',
                ),
            ),
            array(
                array(
                    'host' => 'my-host-here',
                    'port' => 587,
                    'username' => 'some-user',
                    'password' => 'P@SS',
                    'encryption' => 'tls',
                    'auth_mode' => 'login',
                ),
            ),
            array(
                array(
                    'host' => '100.10.100.1',
                    'port' => 65000,
                    'username' => 'some user name that has spaces for some reason',
                    'password' => '123456789123456789123456789123456789',
                    'encryption' => 'tls',
                    'auth_mode' => null,
                ),
            ),
        );
    }

    public function testSwiftMailerServiceIsSwiftMailer()
    {
        $app = new Application();

        $app->register(new SwiftmailerServiceProvider());
        $app->boot();

        $this->assertInstanceOf('Swift_Mailer', $app['mailer']);
    }

    public function testSwiftMailerIgnoresSpoolIfDisabled()
    {
        $app = new Application();

        $app->register(new SwiftmailerServiceProvider());
        $app->boot();

        $app['swiftmailer.use_spool'] = false;

        $app['swiftmailer.spooltransport'] = function () {
            throw new \Exception('Should not be instantiated');
        };

        $this->assertInstanceOf('Swift_Mailer', $app['mailer']);
    }

    public function testSwiftMailerSendsMailsOnFinish()
    {
        $app = new Application();

        $app->register(new SwiftmailerServiceProvider());
        $app->boot();

        $app['swiftmailer.spool'] = $app->share(function () {
            return new SpoolStub();
        });

        $app->get('/', function () use ($app) {
            $app['mailer']->send(\Swift_Message::newInstance());
        });

        $this->assertCount(0, $app['swiftmailer.spool']->getMessages());

        $request = Request::create('/');
        $response = $app->handle($request);
        $this->assertCount(1, $app['swiftmailer.spool']->getMessages());

        $app->terminate($request, $response);
        $this->assertTrue($app['swiftmailer.spool']->hasFlushed);
        $this->assertCount(0, $app['swiftmailer.spool']->getMessages());
    }

    public function testSwiftMailerAvoidsFlushesIfMailerIsUnused()
    {
        $app = new Application();

        $app->register(new SwiftmailerServiceProvider());
        $app->boot();

        $app['swiftmailer.spool'] = $app->share(function () {
            return new SpoolStub();
        });

        $app->get('/', function () use ($app) { });

        $request = Request::create('/');
        $response = $app->handle($request);
        $this->assertCount(0, $app['swiftmailer.spool']->getMessages());

        $app->terminate($request, $response);
        $this->assertFalse($app['swiftmailer.spool']->hasFlushed);
    }

    /**
     * @dataProvider defaultOptions
     */
    public function testSwiftMailerProviderUsesDefaultOptions($options) {
        $app = new Application();
        $app['swiftmailer.options'] = $options;
        $app->register(new SwiftmailerServiceProvider());

        $this->assertEquals($app['swiftmailer.options'],$options);
    }
}
