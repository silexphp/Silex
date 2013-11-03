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

use Silex\Application;
use Silex\LazyUrlMatcher;
use Silex\ServiceProviderInterface;
use Silex\EventListener\LocaleListener;

/**
 * Locale Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LocaleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['locale.listener'] = $app->share(function ($app) {
            $urlMatcher = null;
            if (isset($app['url_matcher'])) {
                $urlMatcher = new LazyUrlMatcher(function () use ($app) {
                    return $app['url_matcher'];
                });
            }

            return new LocaleListener($app, $urlMatcher, $app['request_stack']);
        });

        $app['locale'] = 'en';
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber($app['locale.listener']);
    }
}
