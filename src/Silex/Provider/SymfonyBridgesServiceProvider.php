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

/**
 * Symfony bridges Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SymfonyBridgesServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['symfony_bridges'] = true;

        if (isset($app['symfony_bridges.class_path'])) {
            $app['autoloader']->registerNamespace('Symfony\\Bridge', $app['symfony_bridges.class_path']);
        }
    }
}
