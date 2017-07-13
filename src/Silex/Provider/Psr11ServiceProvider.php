<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Provider;

use Pimple\Container;
use Pimple\Psr11\Container as PsrContainer;
use Pimple\ServiceProviderInterface;
use Silex\Provider\Psr11\ContainerValueResolver;
use Silex\Provider\Psr11\ControllerResolver;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Provides a PSR-11 container and argument value resolver.
 *
 * @author Pascal Luna <skalpa@zetareticuli.org>
 */
class Psr11ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['psr11'] = function ($app) {
            return new PsrContainer($app);
        };

        if (Kernel::VERSION_ID >= 30100) {
            $app->extend('argument_value_resolvers', function ($resolvers, $app) {
                $resolvers[] = new ContainerValueResolver($app['psr11']);

                return $resolvers;
            });
        } else {
            $app->extend('resolver', function ($resolver, $app) {
                return new ControllerResolver($resolver, $app['psr11']);
            });
        }
    }
}
