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
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

            $app['monolog.handler.debug'] = function () use ($app) {
                return new DebugHandler($app['monolog.level']);
            };
        }

        $app['monolog.logger.class'] = $bridge ? 'Symfony\Bridge\Monolog\Logger' : 'Monolog\Logger';

        $app['monolog'] = $app->share(function ($app) {
            $log = new $app['monolog.logger.class']($app['monolog.name']);

            $log->pushHandler($app['monolog.handler']);

            if ($app['debug'] && isset($app['monolog.handler.debug'])) {
                $log->pushHandler($app['monolog.handler.debug']);
            }

            return $log;
        });

        $app['monolog.handler'] = function () use ($app) {
            return new StreamHandler($app['monolog.logfile'], $app['monolog.level']);
        };

        $app['monolog.level'] = function () {
            return Logger::DEBUG;
        };

        $app['monolog.output.debug.verbose'] = true;

        $app['monolog.name'] = 'myapp';
    }

    public function boot(Application $app)
    {
        $app->before(function (Request $request) use ($app) {
            $app['monolog']->addInfo('> '.$request->getMethod().' '.$request->getRequestUri());
        });

        $app->error(function (\Exception $e) use ($app) {
            $exceptions = array($e);
            $current = $e;
            while (null !== $current = $current->getPrevious()) {
                $exceptions[] = $current;
            }
            $count = count($exceptions);
            
            $message = '';
            $severity = 0; //0=error, 1=critical
            foreach ($exceptions as $index => $instance) {
                $message .= ($count > 1 && 0 === $index) ? 'Multiple Uncaught Exceptions:' : '';
                $prefix = ($count > 1) ? sprintf("\n[%d/%d] ", $index + 1, $count) : '';
                $message .= sprintf('%s%s: %s (uncaught exception) at %s line %s', $prefix, get_class($instance), $instance->getMessage(), $instance->getFile(), $instance->getLine());
                if ($app['debug'] && $app['monolog.output.debug.verbose']) {
                    $message .= sprintf("\nStacktrace:\n%s", $instance->getTraceAsString());
                }
                $thisSeverity = ($instance instanceof HttpExceptionInterface && $instance->getStatusCode() < 500) ? 0 : 1;
                if ($thisSeverity > $severity) {
                    $severity = $thisSeverity;
                }
            }
            if (0 === $severity) {
                $app['monolog']->addError($message);
            } else {
                $app['monolog']->addCritical($message);
            }
        }, 255);

        $app->after(function (Request $request, Response $response) use ($app) {
            $app['monolog']->addInfo('< '.$response->getStatusCode());
        });
    }
}
