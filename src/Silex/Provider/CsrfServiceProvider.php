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
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;

/**
 * Symfony CSRF Security component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CsrfServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['csrf.token_manager'] = function ($app) {
            return new CsrfTokenManager($app['csrf.token_generator'], $app['csrf.token_storage']);
        };

        $app['csrf.token_storage'] = function ($app) {
            if (isset($app['session'])) {
                return new SessionTokenStorage($app['session'], $app['csrf.session_namespace']);
            }

            return new NativeSessionTokenStorage($app['csrf.session_namespace']);
        };

        $app['csrf.token_generator'] = function ($app) {
            return new UriSafeTokenGenerator();
        };

        $app['csrf.session_namespace'] = '_csrf';
    }
}
