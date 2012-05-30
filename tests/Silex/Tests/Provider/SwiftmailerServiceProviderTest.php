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

        $spool = new SpoolStub();
        $app['swiftmailer.spooltransport'] = new \Swift_SpoolTransport($spool);

        $app->get('/', function() use ($app) {
            $app['mailer']->send(\Swift_Message::newInstance());
        });

        $this->assertCount(0, $spool->getMessages());

        $request = Request::create('/');
        $response = $app->handle($request);
        $this->assertCount(1, $spool->getMessages());

        $app->terminate($request, $response);
        $this->assertCount(0, $spool->getMessages());
    }
}
