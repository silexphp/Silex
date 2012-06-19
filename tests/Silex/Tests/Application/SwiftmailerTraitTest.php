<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Application;

use Silex\Application;
use Silex\Provider\SwiftmailerServiceProvider;

/**
 * SwiftmailerTrait test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwiftmailerTraitTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->markTestSkipped('PHP 5.4 is required for this test');
        }

        if (!is_dir(__DIR__.'/../../../../vendor/swiftmailer/swiftmailer')) {
            $this->markTestSkipped('Swiftmailer dependency was not installed.');
        }
    }

    public function testMail()
    {
        $app = $this->createApplication();

        $message = $this->getMockBuilder('Swift_Message')->disableOriginalConstructor()->getMock();
        $app['mailer'] = $mailer = $this->getMockBuilder('Swift_Mailer')->disableOriginalConstructor()->getMock();
        $mailer->expects($this->once())
               ->method('send')
               ->with($message)
        ;

        $app->mail($message);
    }

    public function createApplication()
    {
        $app = new SwiftmailerApplication();
        $app->register(new SwiftmailerServiceProvider());

        return $app;
    }
}
