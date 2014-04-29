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
use Silex\Provider\Locale\LocaleListener;
use Silex\Provider\Routing\LazyUrlMatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Locale Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LocaleServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['locale.listener'] = function ($app) {
            $urlMatcher = null;
            if (isset($app['url_matcher'])) {
                $urlMatcher = new LazyUrlMatcher(function () use ($app) {
                    return $app['url_matcher'];
                });
            }

            return new LocaleListener($app, $urlMatcher, $app['request_stack']);
        };

        $app['locale'] = 'en';
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['locale.listener']);
    }
}
