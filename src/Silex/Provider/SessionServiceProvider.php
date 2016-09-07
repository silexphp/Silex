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
use Silex\Provider\Session\SessionListener;
use Silex\Provider\Session\TestSessionListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Symfony HttpFoundation component Provider for sessions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SessionServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['session.test'] = false;

        $app['session'] = function ($app) {
            return new Session($app['session.storage'], $app['session.attribute_bag'], $app['session.flash_bag']);
        };

        $app['session.storage'] = function ($app) {
            if ($app['session.test']) {
                return $app['session.storage.test'];
            }

            return $app['session.storage.native'];
        };

        $app['session.storage.handler'] = function ($app) {
            return new NativeFileSessionHandler($app['session.storage.save_path']);
        };

        $app['session.storage.native'] = function ($app) {
            return new NativeSessionStorage(
                $app['session.storage.options'],
                $app['session.storage.handler']
            );
        };

        $app['session.listener'] = function ($app) {
            return new SessionListener($app);
        };

        $app['session.storage.test'] = function () {
            return new MockFileSessionStorage();
        };

        $app['session.listener.test'] = function ($app) {
            return new TestSessionListener($app);
        };

        $app['session.storage.options'] = [];
        $app['session.default_locale'] = 'en';
        $app['session.storage.save_path'] = null;
        $app['session.attribute_bag'] = null;
        $app['session.flash_bag'] = null;
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['session.listener']);

        if ($app['session.test']) {
            $app['dispatcher']->addSubscriber($app['session.listener.test']);
        }
    }
}
