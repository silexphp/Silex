<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Provider\Routing\RedirectableUrlMatcher;
use Silex\Provider\Routing\LazyUrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Symfony Routing component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RoutingServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['url_generator'] = function ($app) {
            return new UrlGenerator($app['routes'], $app['request_context']);
        };

        $app['url_matcher'] = function () use ($app) {
            return new RedirectableUrlMatcher($app['routes'], $app['request_context']);
        };

        $app['request_context'] = function () use ($app) {
            $context = new RequestContext();

            $context->setHttpPort(isset($app['request.http_port']) ? $app['request.http_port'] : 80);
            $context->setHttpsPort(isset($app['request.https_port']) ? $app['request.https_port'] : 443);

            return $context;
        };

        $app['routing.listener'] = function () use ($app) {
            $urlMatcher = new LazyUrlMatcher(function () use ($app) {
                return $app['url_matcher'];
            });

            return new RouterListener($urlMatcher, $app['request_context'], isset($app['logger']) ? $app['logger'] : null, $app['request_stack']);
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['routing.listener']);
    }
}
