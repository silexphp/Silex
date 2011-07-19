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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler;

/**
 * Defaults error handler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ErrorExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app->error(function (\Exception $exception) use ($app) {
            $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

            if ($app['debug']) {
                $handler = new ExceptionHandler();

                return new Response($handler->getErrorMessage($exception), $code);
            }

            $title = 'Whoops, looks like something went wrong.';
            if ($exception instanceof NotFoundHttpException) {
                $title = 'Sorry, the page you are looking for could not be found.';
            }

            return new Response(sprintf('<!DOCTYPE html><html><head><meta charset="utf-8"><title>%s</title></head><body><h1>%s</h1></body></html>', $title, $title), $code);
        });
    }
}
