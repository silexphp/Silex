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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;
use Symfony\Bridge\Doctrine\Logger\DbalLogger;

/**
 * Doctrine DBAL Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoctrineServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['db.default_options'] = array(
            'driver' => 'pdo_mysql',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        );

        $app['dbs.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($app['dbs.options'])) {
                $app['dbs.options'] = array('default' => isset($app['db.options']) ? $app['db.options'] : array());
            }

            $tmp = $app['dbs.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace($app['db.default_options'], $options);

                if (!isset($app['dbs.default'])) {
                    $app['dbs.default'] = $name;
                }
            }
            $app['dbs.options'] = $tmp;
        });

        $app['dbs'] = function ($app) {
            $app['dbs.options.initializer']();

            $dbs = new Container();
            foreach ($app['dbs.options'] as $name => $options) {
                if ($app['dbs.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $app['db.config'];
                    $manager = $app['db.event_manager'];
                } else {
                    $config = $app['dbs.config'][$name];
                    $manager = $app['dbs.event_manager'][$name];
                }

                $dbs[$name] = function ($dbs) use ($options, $config, $manager) {
                    return DriverManager::getConnection($options, $config, $manager);
                };
            }

            return $dbs;
        };

        $app['dbs.config'] = function ($app) {
            $app['dbs.options.initializer']();

            $configs = new Container();
            $addLogger = isset($app['logger']) && null !== $app['logger'] && class_exists('Symfony\Bridge\Doctrine\Logger\DbalLogger');
            foreach ($app['dbs.options'] as $name => $options) {
                $configs[$name] = new Configuration();
                if ($addLogger) {
                    $configs[$name]->setSQLLogger(new DbalLogger($app['logger'], isset($app['stopwatch']) ? $app['stopwatch'] : null));
                }
            }

            return $configs;
        };

        $app['dbs.event_manager'] = function ($app) {
            $app['dbs.options.initializer']();

            $managers = new Container();
            foreach ($app['dbs.options'] as $name => $options) {
                $managers[$name] = new EventManager();
            }

            return $managers;
        };

        // shortcuts for the "first" DB
        $app['db'] = function ($app) {
            $dbs = $app['dbs'];

            return $dbs[$app['dbs.default']];
        };

        $app['db.config'] = function ($app) {
            $dbs = $app['dbs.config'];

            return $dbs[$app['dbs.default']];
        };

        $app['db.event_manager'] = function ($app) {
            $dbs = $app['dbs.event_manager'];

            return $dbs[$app['dbs.default']];
        };
    }
}
