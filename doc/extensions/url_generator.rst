UrlGenerator
============

The *UrlGeneratorExtension* provides a service for generating
URLs for named routes.

Parameters
----------

None.

Services
--------

* **url_generator**: An instance of
  ``Symfony\Component\Routing\Generator\UrlGenerator``, using the
  ``RouteCollection`` that is provided through the ``routes`` service.
  It has a ``generate`` method, which takes the route name as an argument,
  followed by an array of route parameters.

Registering
-----------

::

    use Silex\Extension\UrlGeneratorExtension;

    $app->register(new UrlGeneratorExtension());

Usage
-----

The UrlGenerator extension provides a ``url_generator`` service.

::

    $app->get('/', function() {
        return 'welcome to the homepage';
    })
    ->bind('homepage');

    $app->get('/hello/{name}', function($name) {
        return "Hello $name!";
    })
    ->bind('hello');

    $app->get('/navigation', function() use ($app) {
        return '<a href="'.$app['url_generator']->generate('homepage').'">Home</a>'.
               ' | '.
               '<a href="'.$app['url_generator']->generate('hello', array('name' => 'Igor')).'">Hello Igor</a>';
    });
