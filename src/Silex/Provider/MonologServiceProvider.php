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
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler;
use Monolog\ErrorHandler;
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Bridge\Monolog\Handler\FingersCrossed\NotFoundActivationStrategy;
use Symfony\Bridge\Monolog\Processor\DebugProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Silex\EventListener\LogListener;

/**
 * Monolog Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MonologServiceProvider implements ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['logger'] = function () use ($app) {
            return $app['monolog'];
        };

        if ($bridge = class_exists('Symfony\Bridge\Monolog\Logger')) {
            if (isset($app['request_stack'])) {
                $app['monolog.not_found_activation_strategy'] = function () use ($app) {
                    $level = MonologServiceProvider::translateLevel($app['monolog.level']);

                    return new NotFoundActivationStrategy($app['request_stack'], ['^/'], $level);
                };
            }
        }

        $app['monolog.logger.class'] = $bridge ? 'Symfony\Bridge\Monolog\Logger' : 'Monolog\Logger';

        $app['monolog'] = function ($app) use ($bridge) {
            $log = new $app['monolog.logger.class']($app['monolog.name']);

            $handler = new Handler\GroupHandler($app['monolog.handlers']);
            if (isset($app['monolog.not_found_activation_strategy'])) {
                $handler = new Handler\FingersCrossedHandler($handler, $app['monolog.not_found_activation_strategy']);
            }

            $log->pushHandler($handler);

            if ($app['debug'] && $bridge) {
                $log->pushProcessor(new DebugProcessor());
            }

            return $log;
        };

        $app['monolog.formatter'] = function () {
            return new LineFormatter();
        };

        $app['monolog.handler'] = $defaultHandler = function () use ($app) {
            $level = MonologServiceProvider::translateLevel($app['monolog.level']);

            $handler = new Handler\StreamHandler($app['monolog.logfile'], $level, $app['monolog.bubble'], $app['monolog.permission']);
            $handler->setFormatter($app['monolog.formatter']);

            return $handler;
        };

        $app['monolog.handlers'] = function () use ($app, $defaultHandler) {
            $handlers = [];

            // enables the default handler if a logfile was set or the monolog.handler service was redefined
            if ($app['monolog.logfile'] || $defaultHandler !== $app->raw('monolog.handler')) {
                $handlers[] = $app['monolog.handler'];
            }

            return $handlers;
        };

        $app['monolog.level'] = function () {
            return Logger::DEBUG;
        };

        $app['monolog.listener'] = function () use ($app) {
            return new LogListener($app['logger'], $app['monolog.exception.logger_filter']);
        };

        $app['monolog.name'] = 'app';
        $app['monolog.bubble'] = true;
        $app['monolog.permission'] = null;
        $app['monolog.exception.logger_filter'] = null;
        $app['monolog.logfile'] = null;
        $app['monolog.use_error_handler'] = function ($app) {
            return !$app['debug'];
        };
    }

    public function boot(Application $app)
    {
        if ($app['monolog.use_error_handler']) {
            ErrorHandler::register($app['monolog']);
        }
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        if (isset($app['monolog.listener'])) {
            $dispatcher->addSubscriber($app['monolog.listener']);
        }
    }

    public static function translateLevel($name)
    {
        // level is already translated to logger constant, return as-is
        if (is_int($name)) {
            return $name;
        }

        $psrLevel = Logger::toMonologLevel($name);

        if (is_int($psrLevel)) {
            return $psrLevel;
        }

        $levels = Logger::getLevels();
        $upper = strtoupper($name);

        if (!isset($levels[$upper])) {
            throw new \InvalidArgumentException("Provided logging level '$name' does not exist. Must be a valid monolog logging level.");
        }

        return $levels[$upper];
    }
}
