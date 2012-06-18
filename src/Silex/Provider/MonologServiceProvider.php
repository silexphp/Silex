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
        if ($bridge = class_exists('Symfony\Bridge\Monolog\Logger')) {
            $app['logger'] = function () use ($app) {
                return $app['monolog'];
            };
        }

        $app['monolog.logger.class'] = $bridge ? 'Symfony\Bridge\Monolog\Logger' : 'Monolog\Logger';

        $app['monolog'] = $app->share(function () use ($app) {

            $log = new $app['monolog.logger.class']($app['monolog.name']);

            $app['monolog.configure']($log);

            return $log;
        });

        $app['monolog.configure'] = $app->protect(function ($log) use ($app) {
            $log->pushHandler($app['monolog.handler']);
        });

        $app['monolog.handler'] = function () use ($app) {
            return new StreamHandler($app['monolog.logfile'], $app['monolog.level']);
        };

        $app['monolog.level'] = function () {
            return Logger::DEBUG;
        };

        $app['monolog.name'] = 'myapp';
    }

    public function boot(Application $app)
    {
        // BC: to be removed before 1.0
        if (isset($app['monolog.class_path'])) {
            throw new \RuntimeException('You have provided the monolog.class_path parameter. The autoloader has been removed from Silex. It is recommended that you use Composer to manage your dependencies and handle your autoloading. If you are already using Composer, you can remove the parameter. See http://getcomposer.org for more information.');
        }

        $app->before(function (Request $request) use ($app) {
            $app['monolog']->addInfo('> '.$request->getMethod().' '.$request->getRequestUri());
        });

        $app->error(function (\Exception $e) use ($app) {
            $app['monolog']->addError($e->getMessage());
        }, 255);

        $app->after(function (Request $request, Response $response) use ($app) {
            $app['monolog']->addInfo('< '.$response->getStatusCode());
        });
    }
}
