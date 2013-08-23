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
use Silex\Provider\UrlGeneratorServiceProvider;

/**
 * UrlGeneratorTrait test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @requires PHP 5.4
 */
class UrlGeneratorTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testUrl()
    {
        $app = $this->createApplication();
        $app['url_generator'] = $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')->disableOriginalConstructor()->getMock();
        $generator->expects($this->once())->method('generate')->with('foo', array(), true);
        $app->url('foo');
    }

    public function testPath()
    {
        $app = $this->createApplication();
        $app['url_generator'] = $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')->disableOriginalConstructor()->getMock();
        $generator->expects($this->once())->method('generate')->with('foo', array(), false);
        $app->path('foo');
    }

    public function testRedirectRoute()
    {
        $app = $this->createApplication();
        $app['url_generator'] = $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')->disableOriginalConstructor()->getMock();
        $generator->expects($this->once())->method('generate')->with('foo', array(), false)->will($this->returnValue('/foo'));
        $response = $app->redirectRoute('foo');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
    }

    public function createApplication()
    {
        $app = new UrlGeneratorApplication();
        $app->register(new UrlGeneratorServiceProvider());

        return $app;
    }
}
