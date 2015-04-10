<?php

namespace Silex\Provider;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Silex\Api\EventListenerProviderInterface;
use Silex\EventListener\ConverterListener;
use Silex\EventListener\MiddlewareListener;
use Silex\EventListener\StringToResponseListener;
use Silex\PimpleAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;

class KernelServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $pimple)
    {
        $pimple['dispatcher'] = function () use ($pimple) {
            return new PimpleAwareEventDispatcher($pimple);
        };
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        if (isset($app['exception_handler'])) {
            $dispatcher->addSubscriber($app['exception_handler']);
        }

        $dispatcher->addSubscriber(new ResponseListener($app['charset']));
        $dispatcher->addSubscriber(new MiddlewareListener($app));
        $dispatcher->addSubscriber(new ConverterListener($app['routes'], $app['callback_resolver']));
        $dispatcher->addSubscriber(new StringToResponseListener());
    }
}
