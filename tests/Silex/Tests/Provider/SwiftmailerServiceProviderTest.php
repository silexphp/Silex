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
}
