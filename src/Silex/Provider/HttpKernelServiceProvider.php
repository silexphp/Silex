<?php

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\CallbackResolver;
use Silex\ControllerResolver;
use Silex\EventListener\ConverterListener;
use Silex\EventListener\MiddlewareListener;
use Silex\EventListener\StringToResponseListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\HttpKernel;

class HttpKernelServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['resolver'] = function ($app) {
            return new ControllerResolver($app, $app['logger']);
        };

        $app['kernel'] = function ($app) {
            return new HttpKernel($app['dispatcher'], $app['resolver'], $app['request_stack']);
        };

        $app['request_stack'] = function () {
            return new RequestStack();
        };

        $app['dispatcher'] = function () {
            return new EventDispatcher();
        };

        $app['callback_resolver'] = function ($app) {
            return new CallbackResolver($app);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber(new ResponseListener($app['charset']));
        $dispatcher->addSubscriber(new MiddlewareListener($app));
        $dispatcher->addSubscriber(new ConverterListener($app['routes'], $app['callback_resolver']));
        $dispatcher->addSubscriber(new StringToResponseListener());
    }
}
