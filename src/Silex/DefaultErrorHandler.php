<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Default error handler for Silex errors
 *
 * Is used when the user error handlers fail
 * to catch the exception.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class DefaultErrorHandler implements EventSubscriberInterface
{
    public function onSilexError(GetResponseForErrorEvent $event)
    {
        $exception = $event->getException();

        $isLocal = in_array($event->getRequest()->server->get('REMOTE_ADDR'), array('127.0.0.1', '::1'));

        $title = 'Whoops, looks like something went wrong.';
        if ($exception instanceof NotFoundHttpException) {
            $title = 'Sorry, the page you are looking for could not be found.';
        }

        $error = $trace = '';
        if ($isLocal) {
            $error = $exception->getMessage();
            $trace = preg_replace('#phar://.*/silex\.phar/#', '', $exception->getTraceAsString());
        }

        $response = new Response(
            $this->renderLayout($isLocal, $title, $error, $trace),
            $exception instanceof HttpException ? $exception->getStatusCode() : 500
        );

        $event->setResponse($response);
    }

    private function renderLayout($isFull, $title, $error, $trace)
    {
        $bodyClass = $isFull ? 'full' : '';

        $error = nl2br($error);
        $trace = nl2br($trace);

        return <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Error</title>
    <style>{$this->renderCss()}</style>
</head>
<body class="$bodyClass">
<div id="content">
    <h1>$title</h1>
    <p id="error">$error</p>
    <a id="showtrace" href="#">[trace]</a>
    <p id="trace">$trace</p>
</div>
<script>{$this->renderJs()}</script>
</body>
</html>
EOF;
    }

    public function renderCss() {
        return <<<EOF
body {
    font-family: Georgia, serif;
    font-size: 16px;
    color: #333;
}
h1 {
    font-size: 30px;
    margin-top: 30px;
    margin-bottom: 30px;
    color: black;
}
#content {
    width: 600px;
    margin: 0 auto;
    padding: 15px 24px;
}
p {
    font-size: 22px;
}
a#showtrace {
    display: none;
    margin-top: 20px;
}
a#showtrace::before {
    margin-top: 20px;
}
p#error {
    margin-bottom: 20px;
}
p#trace {
    display: none;
    font-size: 17px;
    background: #eee;
    padding: 10px;
    border-radius: 7px;
}
body.full #content {
    width: 90%;
}
body.full a#showtrace {
    display: inline;
}
EOF;
    }

    public function renderJs() {
        return <<<EOF
window.onload = function () {
    document.getElementById('showtrace').onclick = function (e) {
        var trace = document.getElementById('trace');
        trace.style.display = (!trace.style.display || trace.style.display == 'none') ? 'block' : 'none';
        e.preventDefault();
    };
};
EOF;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            Events::onSilexError,
        );
    }
}
