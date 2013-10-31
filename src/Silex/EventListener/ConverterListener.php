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

use Silex\CallbackResolver;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Handles converters.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConverterListener implements EventSubscriberInterface
{
    protected $routes;
    protected $callbackResolver;

    /**
     * Constructor.
     *
     * @param RouteCollection  $routes            A RouteCollection instance
     * @param CallbackResolver $callbackResolver  A CallbackResolver instance
     */
    public function __construct(RouteCollection $routes, CallbackResolver $callbackResolver)
    {
        $this->routes = $routes;
        $this->callbackResolver = $callbackResolver;
    }

    /**
     * Handles converters.
     *
     * @param GetResponseEvent $event The event to handle
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $route = $this->routes->get($request->attributes->get('_route'));
        if ($route && $converters = $route->getOption('_converters')) {
            foreach ($converters as $name => $callback) {
                $callback = $this->callbackResolver->isValid($callback) ? $this->callbackResolver->getCallback($callback) : $callback;

                $request->attributes->set($name, call_user_func($callback, $request->attributes->get($name), $request));
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest',
        );
    }
}
