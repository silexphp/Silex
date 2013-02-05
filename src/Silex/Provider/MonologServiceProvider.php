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
    private $app;
    
    public function register(Application $app)
    {
        $this->app = $app;
        
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
        $that = $this;
        
        $app->before(function (Request $request) use ($app) {
            $app['monolog']->addInfo('> '.$request->getMethod().' '.$request->getRequestUri());
        });

        $app->error(function (\Exception $e) use ($app, $that) {
            $that->errorHandler($e);
        }, 255);

        $app->after(function (Request $request, Response $response) use ($app) {
            $app['monolog']->addInfo('< '.$response->getStatusCode());
        });
    }

    private function errorHandler(\Exception $e)
    {
        $exceptions = array($e);
        $current = $e;
        while (null !== $current = $current->getPrevious()) {
            $exceptions[] = $current;
        }
        $count = count($exceptions);
        
        $message = '';
        foreach ($exceptions as $index => $instance) {
            $message .= ($count > 1 && 0 === $index) ? 'Multiple Uncaught Exceptions:' : '';
            $message .= ($count > 1) ? sprintf("\n[%d/%d] ", $index + 1, $count) : '';
            $message .= sprintf('%s: %s (uncaught exception) at %s line %s', get_class($instance), $instance->getMessage(), $instance->getFile(), $instance->getLine());
            if ($this->app['debug'] && $this->app['monolog.output.debug.verbose']) {
                $message .= sprintf("\nStacktrace:\n%s", $instance->getTraceAsString());
            }
        }
        
        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            $this->app['monolog']->addError($message);
        } else {
            $this->app['monolog']->addCritical($message);
        }
    }
}
