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
use Symfony\Component\HttpFoundation\Response;

class SwiftmailerServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/swiftmailer/swiftmailer/lib')) {
            $this->markTestSkipped('Swiftmailer submodule was not installed.');
        }
    }

    public function testSwiftMailerServiceIsSwiftMailer()
    {
        $app = new Application();
        $app->register(new SwiftmailerServiceProvider(), array(
            'swiftmailer.class_path'  => __DIR__.'/../../../../vendor/swiftmailer/swiftmailer/lib/classes',
        ));

        $this->assertInstanceOf('Swift_Mailer', $app['mailer']);
    }

    public function testSwiftMailerSendsMailsOnFinish()
    {
        $app = new Application();
        $app->register(new SwiftmailerServiceProvider(), array(
            'swiftmailer.class_path'  => __DIR__.'/../../../../vendor/swiftmailer/swiftmailer/lib/classes',
        ));

        $app['swiftmailer.transport'] = $app->share(function () use ($app) {
           return new \Swift_Transport_SpoolTransport($app['swiftmailer.transport.eventdispatcher'], new \Swift_MemorySpool());
        });

        $app->get('/', function() use ($app) {
            $app['mailer']->send(\Swift_Message::newInstance());
        });

        /**
         * Checks spool is empty before process
         */
        $this->assertEquals(0, $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']));

        $request = Request::create('/');
        $app->handle($request);
        /**
         * Checks spool has the message that is sent in controller and regenerates it
         */
        $this->assertEquals(1, $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']));
        $app['mailer']->send(\Swift_Message::newInstance());

        /**
         * Terminates app and checks that spool is empty
         */
        $app->terminate($request, new SendMailsResponse('should send e-mails'));
        $this->assertEquals(0, $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']));
    }
}

class SendMailsResponse extends Response
{
    public function send()
    {
        // do nothing
    }
}
