<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Locale;

use Pimple\Container;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Initializes the locale based on the current request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LocaleListener extends BaseLocaleListener
{
    protected $app;

    public function __construct(Container $app, RequestContextAwareInterface $router = null, RequestStack $requestStack = null)
    {
        parent::__construct($app['locale'], $router, $requestStack);

        $this->app = $app;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        parent::onKernelRequest($event);

        $this->app['locale'] = $event->getRequest()->getLocale();
    }
}
