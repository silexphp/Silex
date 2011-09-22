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
use Silex\ServiceProviderInterface;

use Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Symfony HttpFoundation component Provider for sessions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    private $app;

    public function register(Application $app)
    {
        $this->app = $app;

        $app['session'] = $app->share(function () use ($app) {
            return new Session($app['session.storage'], $app['session.default_locale']);
        });

        $app['session.storage'] = $app->share(function () use ($app) {
            return new NativeSessionStorage($app['session.storage.options']);
        });

        $app['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'onKernelRequest'), 128);

        if (!isset($app['session.storage.options'])) {
            $app['session.storage.options'] = array();
        }
        
        if (!isset($app['session.default_locale'])) {
            $app['session.default_locale'] = 'en';
        }
    }

    public function onKernelRequest($event)
    {
        $request = $event->getRequest();
        $request->setSession($this->app['session']);

        // starts the session if a session cookie already exists in the request...
        if ($request->hasPreviousSession()) {
            $request->getSession()->start();
        }
    }
}
