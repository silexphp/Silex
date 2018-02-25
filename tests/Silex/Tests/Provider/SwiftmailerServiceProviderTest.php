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
use Silex\Provider\SwiftmailerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class SwiftmailerServiceProviderTest extends TestCase
{
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

        $app['swiftmailer.spool'] = function () {
            return new SpoolStub();
        };

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

        $app['swiftmailer.spool'] = function () {
            return new SpoolStub();
        };

        $app->get('/', function () use ($app) { });

        $request = Request::create('/');
        $response = $app->handle($request);
        $this->assertCount(0, $app['swiftmailer.spool']->getMessages());

        $app->terminate($request, $response);
        $this->assertFalse($app['swiftmailer.spool']->hasFlushed);
    }

    public function testSwiftMailerSenderAddress()
    {
        $app = new Application();

        $app->register(new SwiftmailerServiceProvider());
        $app->boot();

        $app['swiftmailer.spool'] = function () {
            return new SpoolStub();
        };

        $app['swiftmailer.sender_address'] = 'foo@example.com';

        $app['mailer']->send(\Swift_Message::newInstance());

        $messages = $app['swiftmailer.spool']->getMessages();
        $this->assertCount(1, $messages);

        list($message) = $messages;
        $this->assertEquals('foo@example.com', $message->getReturnPath());
    }

    public function testSwiftMailerPlugins()
    {
        $plugin = $this->getMockBuilder('Swift_Events_TransportChangeListener')->getMock();
        $plugin->expects($this->once())->method('beforeTransportStarted');

        $app = new Application();
        $app->boot();

        $app->register(new SwiftmailerServiceProvider());

        $app['swiftmailer.plugins'] = function ($app) use ($plugin) {
            return [$plugin];
        };

        $dispatcher = $app['swiftmailer.transport.eventdispatcher'];
        $event = $dispatcher->createTransportChangeEvent(new \Swift_Transport_NullTransport($dispatcher));
        $dispatcher->dispatchEvent($event, 'beforeTransportStarted');
    }
}
