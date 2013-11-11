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

use Silex\Api\ServiceProviderInterface;
use Silex\ServiceControllerResolver;

class ServiceControllerServiceProvider implements ServiceProviderInterface
{
    public function register(\Pimple $app)
    {
        $app->extend('resolver', function ($resolver, $app) {
            return new ServiceControllerResolver($resolver, $app['callback_resolver']);
        });
    }
}
