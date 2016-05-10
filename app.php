<?php

// php -S localhost:8000 app.php

require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();
$app['route_class'] = Silex\Route\EmptyPathRoute::class;

$app->mount('/blog', function ($blog) {
    $blog->get('', function () {
        return 'blog main';
    });
    $blog->get('/path', function () {
        return 'blog path';
    });
});

$app->run();
