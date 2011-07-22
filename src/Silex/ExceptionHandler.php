<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler as DebugExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defaults exception handler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExceptionHandler implements EventSubscriberInterface
{
    public function onSilexError(GetResponseForErrorEvent $event)
    {
        $app = $event->getKernel();
        $handler = new DebugExceptionHandler($app['debug']);

        $event->setResponse($handler->createResponse($event->getException()));
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(SilexEvents::ERROR => array('onSilexError', -255));
    }
}
