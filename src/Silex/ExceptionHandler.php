<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler as DebugExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defaults exception handler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExceptionHandler implements EventSubscriberInterface
{
    public function onSilexError(GetResponseForErrorEvent $event)
    {
        $app = $event->getKernel();
        $exception = $event->getException();
        $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        if ($app['debug']) {
            $handler = new DebugExceptionHandler();

            $response = new Response($handler->getErrorMessage($exception), $code);
        } else {
            $title = 'Whoops, looks like something went wrong.';
            if (404 == $code) {
                $title = 'Sorry, the page you are looking for could not be found.';
            }

            $response = new Response(sprintf('<!DOCTYPE html><html><head><meta charset="utf-8"><title>%s</title></head><body><h1>%s</h1></body></html>', $title, $title), $code);
        }

        $event->setResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(SilexEvents::ERROR => array('onSilexError', -255));
    }
}
