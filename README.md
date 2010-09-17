Silex, a simple Web Framework
=============================

Silex is a simple web framework to develop simple websites:

    require_once __DIR__.'/silex.phar';

    use Symfony\Component\HttpFoundation\Response;
    use Silex\Framework;

    $framework = new Framework(array(
        'GET /hello/:name' => function($name)
        {
            return new Response('Hello '.$name);
        },
        'POST /goodbye/:name' => function($name)
        {
            return new Response('Goodbye '.$name);
        },
    ));

    $framework->handle()->send();

Silex is based on [Symfony2][1].

Requirements
------------

Silex works with PHP 5.3.2 or later.

Installation
------------

Installing Silex is as easy as it can get. Download the [`Silex.phar`][2] file
and you're done!

More Information
----------------

Read the documentation of Symfony2 for more information about how you can
leverage Symfony2 features.

License
-------

Silex is licensed under the MIT license.

[1]: http://symfony-reloaded.org/
[2]: http://github.com/fabpot/silex/blob/master/silex.phar
