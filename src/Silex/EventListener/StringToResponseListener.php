<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\EventListener;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles converters.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class StringToResponseListener implements EventSubscriberInterface
{
    /**
     * Handles string responses.
     *
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $response = $event->getControllerResult();
        $response = $response instanceof Response ? $response : new Response((string) $response);

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW => array('onKernelView', -10),
        );
    }
}
