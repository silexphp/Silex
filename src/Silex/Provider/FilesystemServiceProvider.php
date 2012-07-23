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
use Symfony\Component\Filesystem\Filesystem;

/**
 * Symfony Filesystem component Provider.
 *
 * @author Romain Neutron <imprec@gmail.com>
 */
class FilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['filesystem'] = $app->share(function () use ($app) {

            return new Filesystem();
        });
    }

    public function boot(Application $app)
    {
    }
}
