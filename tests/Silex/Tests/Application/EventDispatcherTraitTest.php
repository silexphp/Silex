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

use Symfony\Component\EventDispatcher\Event;

/**
 * EventDispatcherTraitTest test cases.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
class EventDispatcherTraitTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $this->markTestSkipped('PHP 5.4 is required for this test');
        }
    }

    public function testOnWithClosure()
    {
        $app = $this->createApplication();
        $app['rained'] = false;

        $app->on('rain', function(Event $e) use ($app) {
            $app['rained'] = true;
        });

        $app['dispatcher']->dispatch('rain');

        $this->assertTrue($app['rained']);
    }

    public function testOnWithService()
    {
        $app = $this->createApplication();
        $event = new Event();

        $listener = $this->getMock('stdObject', array('listen'));
        $listener->expects($this->once())
            ->method('listen')
            ->with($this->equalTo($event));
        $app['listener'] = $listener;

        $app->on('myevent', array('listener', 'listen'));

        $app['dispatcher']->dispatch('myevent', $event);
    }

    public function testOnFalseStopPropagation()
    {
        $app = $this->createApplication();

        $listener = $this->getMock('stdObject', array('listen1', 'listen2'));
        $listener->expects($this->once())
            ->method('listen1')
            ->will($this->returnValue(false));
        $listener->expects($this->never())
            ->method('listen2');

        $app->on('myevent', array($listener, 'listen2'), -1);
        $app->on('myevent', array($listener, 'listen1'), 1);

        $app['dispatcher']->dispatch('myevent');
    }

    public function createApplication()
    {
        $app = new EventDispatcherApplication();

        return $app;
    }
}
