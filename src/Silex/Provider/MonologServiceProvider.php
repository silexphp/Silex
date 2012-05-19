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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Silex\Application;
use Silex\ServiceProviderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Monolog Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MonologServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['monolog'] = $app->share(function () use ($app) {
            $log = new Logger(isset($app['monolog.name']) ? $app['monolog.name'] : 'myapp');

            $app['monolog.configure']($log);

            return $log;
        });

        $app['monolog.configure'] = $app->protect(function ($log) use ($app) {
            $log->pushHandler($app['monolog.handler']);
        });

        $app['monolog.handler'] = function () use ($app) {
            return new StreamHandler($app['monolog.logfile'], $app['monolog.level']);
        };

        if (!isset($app['monolog.level'])) {
            $app['monolog.level'] = function () {
                return Logger::DEBUG;
            };
        }

        if (isset($app['monolog.class_path'])) {
            $app['autoloader']->registerNamespace('Monolog', $app['monolog.class_path']);
        }

        $app->before(function (Request $request) use ($app) {
            $app['monolog']->addInfo('> '.$request->getMethod().' '.$request->getRequestUri());
        });

        $app->error(function (\Exception $e) use ($app) {
            $app['monolog']->addError($e->getMessage());
        });

        $app->after(function (Request $request, Response $response) use ($app) {
            $app['monolog']->addInfo('< '.$response->getStatusCode());
        });
    }
}
