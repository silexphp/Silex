<?php

require_once __DIR__.'/silex.phar';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Framework;

$hello = function ($name)
{
    return new Response('Hello '.$name);
};

$framework = new Framework(array(
    '/hello/:name' => $hello,
));

// Simulate a request without a Client
//$request = Request::create('/hello/Fabien');
//$framework->handle($request)->send();

$framework->handle()->send();
