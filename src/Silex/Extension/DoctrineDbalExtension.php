<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Extension;

use Silex\Application;
use Silex\ExtensionInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;

class DoctrineDbalExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        if (isset($app['dbal.options'])) {
            $app['dbal.options'] = array_replace(array(
                'driver'   => 'pdo_mysql',
                'dbname'   => null,
                'host'     => 'localhost',
                'user'     => 'root',
                'password' => null,
            ), isset($app['dbal.options']) ? $app['dbal.options'] : array());

            $app['dbal'] = $app->share(function () use ($app) {
                return DriverManager::getConnection($app['dbal.options'], $app['dbal.config'], $app['dbal.event_manager']);
            });

            $app['dbal.config'] = $app->share(function () {
                return new Configuration();
            });

            $app['dbal.event_manager'] = $app->share(function () {
                return new EventManager();
            });
        } elseif (isset($app['dbal.dbs']) && is_array($app['dbal.dbs'])) {
            $firstConnection = true; 
            foreach ($app['dbal.dbs'] as $connection => $options) {

                $app['dbal.connection.'.$connection.'.options'] = $options;
                $app['dbal.connection.'.$connection] = $app->share(function () use ($app, $options, $connection) {
                    return DriverManager::getConnection($options, $app['dbal.connection.'.$connection.'.config'], $app['dbal.connection.'.$connection.'.event_manager']);
                });
                $app['dbal.connection.'.$connection.'.config'] = $app->share(function () {
                    return new Configuration();
                });

                $app['dbal.connection.'.$connection.'.event_manager'] = $app->share(function () {
                    return new EventManager();
                });

                if ($firstConnection) {
                    $app['dbal'] = $app->share(function () use ($app, $options, $connection) {
                        return DriverManager::getConnection($options, $app['dbal.connection.'.$connection.'.config'], $app['dbal.connection.'.$connection.'.event_manager']);
                    });
                    $app['dbal.config'] = $app->share(function () {
                        return new Configuration();
                    });
                    $app['dbal.event_manager'] =     $app->share(function () {
                        return new EventManager();
                    });
                }
                $firstConnection = false;
            }
            unset($app['dbal.dbs']);
        } else {
            throw new Exception ('DoctrineDbalExtension requires options to be configured.');
        }

        if (isset($app['dbal.dbal.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\DBAL', $app['dbal.dbal.class_path']);
        }

        if (isset($app['dbal.common.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\Common', $app['dbal.common.class_path']);
        }
    }
}