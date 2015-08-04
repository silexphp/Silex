<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\EventListener;

use Silex\EventListener\LogListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * LogListener.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
class LogListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testRequestListener()
    {
        $logger = $this->getMock('Psr\\Log\\LoggerInterface');
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('> GET /foo'))
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new LogListener($logger));

        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');

        $dispatcher->dispatch(KernelEvents::REQUEST, new GetResponseEvent($kernel, Request::create('/subrequest'), HttpKernelInterface::SUB_REQUEST), 'Skip sub requests');

        $dispatcher->dispatch(KernelEvents::REQUEST, new GetResponseEvent($kernel, Request::create('/foo'), HttpKernelInterface::MASTER_REQUEST), 'Log master requests');
    }

    public function testResponseListener()
    {
        $logger = $this->getMock('Psr\\Log\\LoggerInterface');
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('< 301'))
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new LogListener($logger));

        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');

        $dispatcher->dispatch(KernelEvents::RESPONSE, new FilterResponseEvent($kernel, Request::create('/foo'), HttpKernelInterface::SUB_REQUEST, Response::create('subrequest', 200)), 'Skip sub requests');

        $dispatcher->dispatch(KernelEvents::RESPONSE, new FilterResponseEvent($kernel, Request::create('/foo'), HttpKernelInterface::MASTER_REQUEST, Response::create('bar', 301)), 'Log master requests');
    }

    public function testExceptionListener()
    {
        $logger = $this->getMock('Psr\\Log\\LoggerInterface');
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->equalTo('RuntimeException: Fatal error (uncaught exception) at '.__FILE__.' line '.(__LINE__ + 14)))
        ;

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->equalTo('Symfony\Component\HttpKernel\Exception\HttpException: Http error (uncaught exception) at '.__FILE__.' line '.(__LINE__ + 10)))
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new LogListener($logger));

        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');

        $dispatcher->dispatch(KernelEvents::EXCEPTION, new GetResponseForExceptionEvent($kernel, Request::create('/foo'), HttpKernelInterface::SUB_REQUEST, new \RuntimeException('Fatal error')));

        $dispatcher->dispatch(KernelEvents::EXCEPTION, new GetResponseForExceptionEvent($kernel, Request::create('/foo'), HttpKernelInterface::SUB_REQUEST, new HttpException(400, 'Http error')));
    }
}
