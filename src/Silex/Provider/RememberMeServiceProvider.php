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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;
use Symfony\Component\Security\Http\Firewall\RememberMeListener;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;
use Symfony\Component\Security\Http\RememberMe\ResponseListener;

/**
 * Remember-me authentication for the SecurityServiceProvider.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
class RememberMeServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['security.remember_me.response_listener'] = function ($app) {
            if (!isset($app['security.token_storage'])) {
                throw new \LogicException('You must register the SecurityServiceProvider to use the RememberMeServiceProvider');
            }

            return new ResponseListener();
        };

        $app['security.authentication_listener.factory.remember_me'] = $app->protect(function ($name, $options) use ($app) {
            if (empty($options['key'])) {
                $options['key'] = $name;
            }

            if (!isset($app['security.remember_me.service.'.$name])) {
                $app['security.remember_me.service.'.$name] = $app['security.remember_me.service._proto']($name, $options);
            }

            if (!isset($app['security.authentication_listener.'.$name.'.remember_me'])) {
                $app['security.authentication_listener.'.$name.'.remember_me'] = $app['security.authentication_listener.remember_me._proto']($name, $options);
            }

            if (!isset($app['security.authentication_provider.'.$name.'.remember_me'])) {
                $app['security.authentication_provider.'.$name.'.remember_me'] = $app['security.authentication_provider.remember_me._proto']($name, $options);
            }

            return array(
                'security.authentication_provider.'.$name.'.remember_me',
                'security.authentication_listener.'.$name.'.remember_me',
                null, // entry point
                'remember_me',
            );
        });

        $app['security.remember_me.service._proto'] = $app->protect(function ($providerKey, $options) use ($app) {
            return function () use ($providerKey, $options, $app) {
                $options = array_replace(array(
                    'name' => 'REMEMBERME',
                    'lifetime' => 31536000,
                    'path' => '/',
                    'domain' => null,
                    'secure' => false,
                    'httponly' => true,
                    'always_remember_me' => false,
                    'remember_me_parameter' => '_remember_me',
                ), $options);

                return new TokenBasedRememberMeServices(array($app['security.user_provider.'.$providerKey]), $options['key'], $providerKey, $options, $app['logger']);
            };
        });

        $app['security.authentication_listener.remember_me._proto'] = $app->protect(function ($providerKey) use ($app) {
            return function () use ($app, $providerKey) {
                $listener = new RememberMeListener(
                    $app['security.token_storage'],
                    $app['security.remember_me.service.'.$providerKey],
                    $app['security.authentication_manager'],
                    $app['logger'],
                    $app['dispatcher']
                );

                return $listener;
            };
        });

        $app['security.authentication_provider.remember_me._proto'] = $app->protect(function ($name, $options) use ($app) {
            return function () use ($app, $name, $options) {
                return new RememberMeAuthenticationProvider($app['security.user_checker'], $options['key'], $name);
            };
        });
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['security.remember_me.response_listener']);
    }
}
