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

        $test = $this;

        /**
         * This gets executed before SwiftmailerServiceProvider $app->finish thanks to higher priority
         * flushQueue should return 1 if spool has not been flushed
         */
        $app->finish(function() use ($app, $test) {
            $test->assertEquals(1, $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']));
            /**
             * We add a new message that should be flushed with $app->finish()
             */
            $app['mailer']->send(\Swift_Message::newInstance());
        }, 1);

        /**
         * This gets executed after SwiftmailerServiceProvider $app->finish thanks to higher priority
         * flushQueue should return 0 even having added a message in method above
         */
        $app->finish(function() use ($app, $test) {
            $test->assertEquals(0, $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']));
        }, -1);

        $app->get('/', function() use ($app) {
            $app['mailer']->send(\Swift_Message::newInstance());
            return new SendMailsResponse('should send e-mails');
        });

        $request = Request::create('/');
        $app->run($request);
    }
}

class SendMailsResponse extends Response
{
    public function send()
    {
        // do nothing
    }
}
