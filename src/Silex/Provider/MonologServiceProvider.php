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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $monologServiceProvider = new \Pimplex\ServiceProvider\MonologServiceProvider();
        $monologServiceProvider->register($app);
    }

    public function boot(Application $app)
    {
        $app->before(function (Request $request) use ($app) {
            $app['monolog']->addInfo('> '.$request->getMethod().' '.$request->getRequestUri());
        });

        /*
         * Priority -4 is used to come after those from SecurityServiceProvider (0)
         * but before the error handlers added with Silex\Application::error (defaults to -8)
         */
        $app->error(function (\Exception $e) use ($app) {
            $message = sprintf('%s: %s (uncaught exception) at %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                $app['monolog']->addError($message, array('exception' => $e));
            } else {
                $app['monolog']->addCritical($message, array('exception' => $e));
            }
        }, -4);

        $app->after(function (Request $request, Response $response) use ($app) {
            if ($response instanceof RedirectResponse) {
                $app['monolog']->addInfo('< '.$response->getStatusCode().' '.$response->getTargetUrl());
            } else {
                $app['monolog']->addInfo('< '.$response->getStatusCode());
            }
        });
    }
}
