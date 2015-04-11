<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Translation;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Initializes the Translator locale based on the Request locale.
 *
 * This listener works in 2 modes:
 *
 *  * 2.3 compatibility mode where you must call setRequest whenever the Request changes.
 *  * 2.4+ mode where you must pass a RequestStack instance in the constructor.
 *
 * @author Mathieu Poisbeau <freepius44@gmail.com>
 */
class TranslatorListener implements EventSubscriberInterface
{
    protected $translator;
    protected $requestStack;

    /**
     * RequestStack will become required in 3.0.
     */
    public function __construct(TranslatorInterface $translator, RequestStack $requestStack = null)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    /**
     * Sets the current Request.
     *
     * This method was used to synchronize the Request, but as the HttpKernel
     * is doing that automatically now, you should never call it directly.
     * It is kept public for BC with the 2.3 version.
     *
     * @param Request|null $request A Request instance
     *
     * @deprecated Deprecated since version 2.4, to be removed in 3.0.
     */
    public function setRequest(Request $request = null)
    {
        if (null === $request) {
            return;
        }

        $this->translator->setLocale($request->getLocale());
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->translator->setLocale($event->getRequest()->getLocale());
    }

    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        if (null === $this->requestStack) {
            return; // removed when requestStack is required
        }

        if (null !== $parentRequest = $this->requestStack->getParentRequest()) {
            $this->translator->setLocale($parentRequest->getLocale());
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 0)),
            KernelEvents::FINISH_REQUEST => array(array('onKernelFinishRequest', 0)),
        );
    }
}
