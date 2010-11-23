<?php

require_once __DIR__.'/silex.phar';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Framework;

$hello = function ($name)
{
    return new Response('Hello '.$name);
};

$goodbye = function($name)
{
    return new Response('Goodbye '.$name);
};

$framework = new Framework(array(
    'GET /hello/:name'    => $hello,
    'POST /goodbye/:name' => $goodbye,
));

// Simulate a hello request without a Client
//$request = Request::create('/hello/Fabien');
//$framework->run($request);

// Simulate a goodbye request without a Client
//$request = Request::create('/goodbye/Fabien', 'post');
//$framework->run($request);

$framework->run();
