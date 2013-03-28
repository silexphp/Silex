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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RequestContext;

/**
 * Inject the query string into the RequestContext for Symfony versions <= 2.2
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class RequestContextQueryStringListener implements EventSubscriberInterface
{
    private $context;

    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->shouldRun()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->server->get('QUERY_STRING') !== '') {
            $this->context->setParameter('QUERY_STRING', $request->server->get('QUERY_STRING'));
        }
    }

    private function shouldRun()
    {
        return !method_exists('Symfony\Component\Routing\RequestContext', 'getQueryString');
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 1024),
        );
    }
}
