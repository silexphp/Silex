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
            $app['dbal.dbs'] = array('default' => $app['dbal.options']);
        }
        if (isset($app['dbal.dbs']) && is_array($app['dbal.dbs'])) {
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

                if ($firstConnection || !empty($options['default'])) {
                    $app['dbal'] = function() use ($app, $connection) {
                        return $app['dbal.connection.'.$connection];
                    };
                }
                $firstConnection = false;
            }
            unset($app['dbal.dbs']);
        }

        if (isset($app['dbal.dbal.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\DBAL', $app['dbal.dbal.class_path']);
        }

        if (isset($app['dbal.common.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\Common', $app['dbal.common.class_path']);
        }
    }
}