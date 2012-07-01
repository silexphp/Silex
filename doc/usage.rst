Usage
=====

This chapter describes how to use Silex.

Installation
------------

If you want to get started fast, `download`_ Silex as an archive and extract
it, you should have the following directory structure:

.. code-block:: text

    ├── composer.json
    ├── composer.lock
    ├── vendor
    │   └── ...
    └── web
        └── index.php

If you want more flexibility, use Composer instead. Create a
``composer.json``:

.. code-block:: json

    {
        "require": {
            "silex/silex": "1.0.*"
        }
    }

And run Composer to install Silex and all its dependencies:

.. code-block:: bash

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar install

Upgrading
---------

Upgrading Silex to the latest version is as easy as running the ``update``
command::

    $ php composer.phar update

Bootstrap
---------

To bootstrap Silex, all you need to do is require the ``vendor/autoload.php``
file and create an instance of ``Silex\Application``. After your controller
definitions, call the ``run`` method on your application::

    // web/index.php

    require_once __DIR__.'/../vendor/autoload.php';

    $app = new Silex\Application();

    // definitions

    $app->run();

Then, you have to configure your web server (read the dedicated chapter for
more information).

.. tip::

    When developing a website, you might want to turn on the debug mode to
    ease debugging::

        $app['debug'] = true;

.. tip::

    If your application is hosted behind a reverse proxy and you want Silex to
    trust the ``X-Forwarded-For*`` headers, you will need to run your
    application like this::

        use Symfony\Component\HttpFoundation\Request;

        Request::trustProxyData();
        $app->run();

Routing
-------

In Silex you define a route and the controller that is called when that
route is matched.

A route pattern consists of:

* *Pattern*: The route pattern defines a path that points to a resource. The
  pattern can include variable parts and you are able to set RegExp
  requirements for them.

* *Method*: One of the following HTTP methods: ``GET``, ``POST``, ``PUT``
  ``DELETE``. This describes the interaction with the resource. Commonly only
  ``GET`` and ``POST`` are used, but it is possible to use the others as well.

The controller is defined using a closure like this::

    function () {
        // do something
    }

Closures are anonymous functions that may import state from outside of their
definition. This is different from globals, because the outer state does not
have to be global. For instance, you could define a closure in a function and
import local variables of that function.

.. note::

    Closures that do not import scope are referred to as lambdas. Because in
    PHP all anonymous functions are instances of the ``Closure`` class, we
    will not make a distinction here.

The return value of the closure becomes the content of the page.

There is also an alternate way for defining controllers using a class method.
The syntax for that is ``ClassName::methodName``. Static methods are also
possible.

Example GET route
~~~~~~~~~~~~~~~~~

Here is an example definition of a ``GET`` route::

    $blogPosts = array(
        1 => array(
            'date'      => '2011-03-29',
            'author'    => 'igorw',
            'title'     => 'Using Silex',
            'body'      => '...',
        ),
    );

    $app->get('/blog', function () use ($blogPosts) {
        $output = '';
        foreach ($blogPosts as $post) {
            $output .= $post['title'];
            $output .= '<br />';
        }

        return $output;
    });

Visiting ``/blog`` will return a list of blog post titles. The ``use``
statement means something different in this context. It tells the closure to
import the $blogPosts variable from the outer scope. This allows you to use it
from within the closure.

Dynamic routing
~~~~~~~~~~~~~~~

Now, you can create another controller for viewing individual blog posts::

    $app->get('/blog/show/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
        if (!isset($blogPosts[$id])) {
            $app->abort(404, "Post $id does not exist.");
        }

        $post = $blogPosts[$id];

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

This route definition has a variable ``{id}`` part which is passed to the
closure.

When the post does not exist, we are using ``abort()`` to stop the request
early. It actually throws an exception, which we will see how to handle later
on.

Example POST route
~~~~~~~~~~~~~~~~~~

POST routes signify the creation of a resource. An example for this is a
feedback form. We will use the ``mail`` function to send an e-mail::

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    $app->post('/feedback', function (Request $request) {
        $message = $request->get('message');
        mail('feedback@yoursite.com', '[YourSite] Feedback', $message);

        return new Response('Thank you for your feedback!', 201);
    });

It is pretty straightforward.

.. note::

    There is a :doc:`SwiftmailerServiceProvider <providers/swiftmailer>`
    included that you can use instead of ``mail()``.

The current ``request`` is automatically injected by Silex to the Closure
thanks to the type hinting. It is an instance of `Request
<http://api.symfony.com/master/Symfony/Component/HttpFoundation/Request.html>`_,
so you can fetch variables using the request ``get`` method.

Instead of returning a string we are returning an instance of `Response
<http://api.symfony.com/master/Symfony/Component/HttpFoundation/Response.html>`_.
This allows setting an HTTP status code, in this case it is set to ``201
Created``.

.. note::

    Silex always uses a ``Response`` internally, it converts strings to
    responses with status code ``200 Ok``.

Other methods
~~~~~~~~~~~~~

You can create controllers for most HTTP methods. Just call one of these
methods on your application: ``get``, ``post``, ``put``, ``delete``. You can
also call ``match``, which will match all methods::

    $app->match('/blog', function () {
        ...
    });

You can then restrict the allowed methods via the ``method`` method::

    $app->match('/blog', function () {
        ...
    })
    ->method('PATCH');

You can match multiple methods with one controller using regex syntax::

    $app->match('/blog', function () {
        ...
    })
    ->method('PUT|POST');

.. note::

    The order in which the routes are defined is significant. The first
    matching route will be used, so place more generic routes at the bottom.


Route variables
~~~~~~~~~~~~~~~

As it has been shown before you can define variable parts in a route like
this::

    $app->get('/blog/show/{id}', function ($id) {
        ...
    });

It is also possible to have more than one variable part, just make sure the
closure arguments match the names of the variable parts::

    $app->get('/blog/show/{postId}/{commentId}', function ($postId, $commentId) {
        ...
    });

While it's not suggested, you could also do this (note the switched
arguments)::

    $app->get('/blog/show/{postId}/{commentId}', function ($commentId, $postId) {
        ...
    });

You can also ask for the current Request and Application objects::

    $app->get('/blog/show/{id}', function (Application $app, Request $request, $id) {
        ...
    });

.. note::

    Note for the Application and Request objects, Silex does the injection
    based on the type hinting and not on the variable name::

        $app->get('/blog/show/{id}', function (Application $foo, Request $bar, $id) {
            ...
        });

Route variables converters
~~~~~~~~~~~~~~~~~~~~~~~~~~

Before injecting the route variables into the controller, you can apply some
converters::

    $app->get('/user/{id}', function ($id) {
        // ...
    })->convert('id', function ($id) { return (int) $id; });

This is useful when you want to convert route variables to objects as it
allows to reuse the conversion code across different controllers::

    $userProvider = function ($id) {
        return new User($id);
    };

    $app->get('/user/{user}', function (User $user) {
        // ...
    })->convert('user', $userProvider);

    $app->get('/user/{user}/edit', function (User $user) {
        // ...
    })->convert('user', $userProvider);

The converter callback also receives the ``Request`` as its second argument::

    $callback = function ($post, Request $request) {
        return new Post($request->attributes->get('slug'));
    };

    $app->get('/blog/{id}/{slug}', function (Post $post) {
        // ...
    })->convert('post', $callback);

Requirements
~~~~~~~~~~~~

In some cases you may want to only match certain expressions. You can define
requirements using regular expressions by calling ``assert`` on the
``Controller`` object, which is returned by the routing methods.

The following will make sure the ``id`` argument is numeric, since ``\d+``
matches any amount of digits::

    $app->get('/blog/show/{id}', function ($id) {
        ...
    })
    ->assert('id', '\d+');

You can also chain these calls::

    $app->get('/blog/show/{postId}/{commentId}', function ($postId, $commentId) {
        ...
    })
    ->assert('postId', '\d+')
    ->assert('commentId', '\d+');

Default values
~~~~~~~~~~~~~~

You can define a default value for any route variable by calling ``value`` on
the ``Controller`` object::

    $app->get('/{pageName}', function ($pageName) {
        ...
    })
    ->value('pageName', 'index');

This will allow matching ``/``, in which case the ``pageName`` variable will
have the value ``index``.

Named routes
~~~~~~~~~~~~

Some providers (such as ``UrlGeneratorProvider``) can make use of named
routes. By default Silex will generate a route name for you, that cannot
really be used. You can give a route a name by calling ``bind`` on the
``Controller`` object that is returned by the routing methods::

    $app->get('/', function () {
        ...
    })
    ->bind('homepage');

    $app->get('/blog/show/{id}', function ($id) {
        ...
    })
    ->bind('blog_post');


.. note::

    It only makes sense to name routes if you use providers that make use of
    the ``RouteCollection``.

Before, after and finish filters
--------------------------------

Silex allows you to run code before, after every request and even after the
response has been sent. This happens through ``before``, ``after`` and
``finish`` filters. All you need to do is pass a closure::

    $app->before(function () {
        // set up
    });

    $app->after(function () {
        // tear down
    });

    $app->finish(function () {
        // after response has been sent
    });

The before filter has access to the current Request, and can short-circuit the
whole rendering by returning a Response::

    $app->before(function (Request $request) {
        // redirect the user to the login screen if access to the Resource is protected
        if (...) {
            return new RedirectResponse('/login');
        }
    });

The after filter has access to the Request and the Response::

    $app->after(function (Request $request, Response $response) {
        // tweak the Response
    });

The finish filter has access to the Request and the Response::

    $app->finish(function (Request $request, Response $response) {
        // send e-mails ...
    });

.. note::

    The filters are only run for the "master" Request.

Route middlewares
-----------------

Route middlewares are PHP callables which are triggered when their associated
route is matched:

* ``before`` middlewares are fired just before the route callback, but after
  the application ``before`` filters;

* ``after`` middlewares are fired just after the route callback, but before
  the application ``after`` filters.

This can be used for a lot of use cases; for instance, here is a simple
"anonymous/logged user" check::

    $mustBeAnonymous = function (Request $request) use ($app) {
        if ($app['session']->has('userId')) {
            return $app->redirect('/user/logout');
        }
    };

    $mustBeLogged = function (Request $request) use ($app) {
        if (!$app['session']->has('userId')) {
            return $app->redirect('/user/login');
        }
    };

    $app->get('/user/subscribe', function () {
        ...
    })
    ->before($mustBeAnonymous);

    $app->get('/user/login', function () {
        ...
    })
    ->before($mustBeAnonymous);

    $app->get('/user/my-profile', function () {
        ...
    })
    ->before($mustBeLogged);

The ``before`` and ``after`` methods can be called several times for a given
route, in which case they are triggered in the same order as you added them to
the route.

For convenience, the ``before`` middlewares are called with the current
``Request`` instance as an argument and the ``after`` middlewares are called
with the current ``Request`` and ``Response`` instance as arguments.

If any of the before middlewares returns a Symfony HTTP Response, it will
short-circuit the whole rendering: the next middlewares won't be run, neither
the route callback. You can also redirect to another page by returning a
redirect response, which you can create by calling the Application
``redirect`` method.

.. note::

    If a before middleware does not return a Symfony HTTP Response or
    ``null``, a ``RuntimeException`` is thrown.

Global Configuration
--------------------

If a controller setting must be applied to all controllers (a converter, a
middleware, a requirement, or a default value), you can configure it on
``$app['controllers']``, which holds all application controllers::

    $app['controllers']
        ->value('id', '1')
        ->assert('id', '\d+')
        ->requireHttps()
        ->method('get')
        ->convert('id', function () { // ... })
        ->before(function () { // ... })
    ;

These settings are applied to already registered controllers and they become
the defaults for new controllers.

.. note::

    The global configuration does not apply to controller providers you might
    mount as they have their own global configuration (see the Modularity
    paragraph below).

Error handlers
--------------

If some part of your code throws an exception you will want to display some
kind of error page to the user. This is what error handlers do. You can also
use them to do additional things, such as logging.

To register an error handler, pass a closure to the ``error`` method which
takes an ``Exception`` argument and returns a response::

    use Symfony\Component\HttpFoundation\Response;

    $app->error(function (\Exception $e, $code) {
        return new Response('We are sorry, but something went terribly wrong.', $code);
    });

You can also check for specific errors by using the ``$code`` argument, and
handle them differently::

    use Symfony\Component\HttpFoundation\Response;

    $app->error(function (\Exception $e, $code) {
        switch ($code) {
            case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                $message = 'We are sorry, but something went terribly wrong.';
        }

        return new Response($message, $code);
    });

You can restrict an error handler to only handle some Exception classes by
setting a more specific type hint for the Closure argument::

    $app->error(function (\LogicException $e, $code) {
        // this handler will only \LogicException exceptions
        // and exceptions that extends \LogicException
    });

If you want to set up logging you can use a separate error handler for that.
Just make sure you register it before the response error handlers, because
once a response is returned, the following handlers are ignored.

.. note::

    Silex ships with a provider for `Monolog
    <https://github.com/Seldaek/monolog>`_ which handles logging of errors.
    Check out the *Providers* chapter for details.

.. tip::

    Silex comes with a default error handler that displays a detailed error
    message with the stack trace when **debug** is true, and a simple error
    message otherwise. Error handlers registered via the ``error()`` method
    always take precedence but you can keep the nice error messages when debug
    is turned on like this::

        use Symfony\Component\HttpFoundation\Response;

        $app->error(function (\Exception $e, $code) use ($app) {
            if ($app['debug']) {
                return;
            }

            // logic to handle the error and return a Response
        });

The error handlers are also called when you use ``abort`` to abort a request
early::

    $app->get('/blog/show/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
        if (!isset($blogPosts[$id])) {
            $app->abort(404, "Post $id does not exist.");
        }

        return new Response(...);
    });

Redirects
---------

You can redirect to another page by returning a redirect response, which you
can create by calling the ``redirect`` method::

    $app->get('/', function () use ($app) {
        return $app->redirect('/hello');
    });

This will redirect from ``/`` to ``/hello``.

Forwards
--------

When you want to delegate the rendering to another controller, without a
round-trip to the browser (as for a redirect), use an internal sub-request::

    use Symfony\Component\HttpKernel\HttpKernelInterface;

    $app->get('/', function () use ($app) {
        // redirect to /hello
        $subRequest = Request::create('/hello', 'GET');

        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    });

.. tip::

    If you are using ``UrlGeneratorProvider``, you can also generate the URI::

        $request = Request::create($app['url_generator']->generate('hello'), 'GET');

Modularity
----------

When your application starts to define too many controllers, you might want to
group them logically::

    // define controllers for a blog
    $blog = $app['controllers_factory'];
    $blog->get('/', function () {
        return 'Blog home page';
    });
    // ...

    // define controllers for a forum
    $forum = $app['controllers_factory'];
    $forum->get('/', function () {
        return 'Forum home page';
    });

    // define "global" controllers
    $app->get('/', function () {
        return 'Main home page';
    });

    $app->mount('/blog', $blog);
    $app->mount('/forum', $forum);

.. note::

    ``$app['controllers_factory']`` is a factory that returns a new instance
    of ``ControllerCollection`` when used.

``mount()`` prefixes all routes with the given prefix and merges them into the
main Application. So, ``/`` will map to the main home page, ``/blog/`` to the
blog home page, and ``/forum/`` to the forum home page.

.. caution::

    When mounting a route collection under ``/blog``, it is not possible to
    define a route for the ``/blog`` URL. The shortest possible URL is
    ``/blog/``.

.. note::

    When calling ``get()``, ``match()``, or any other HTTP methods on the
    Application, you are in fact calling them on a default instance of
    ``ControllerCollection`` (stored in ``$app['controllers']``).

Another benefit is the ability to apply settings on a set of controllers very
easily. Building on the example from the middleware section, here is how you
would secure all controllers for the backend collection::

    $backend = $app['controllers_factory'];

    // ensure that all controllers require logged-in users
    $backend->before($mustBeLogged);

.. tip::

    For a better readability, you can split each controller collection into a
    separate file::

        // blog.php
        $blog = $app['controllers_factory'];
        $blog->get('/', function () { return 'Blog home page'; });

        return $blog;

        // app.php
        $app->mount('/blog', include 'blog.php');

    Instead of requiring a file, you can also create a :doc:`Controller
    provider </providers#controllers-providers>`.

JSON
----

If you want to return JSON data, you can use the ``json`` helper method.
Simply pass it your data, status code and headers, and it will create a JSON
response for you::

    $app->get('/users/{id}', function ($id) use ($app) {
        $user = getUser($id);

        if (!$user) {
            $error = array('message' => 'The user was not found.');
            return $app->json($error, 404);
        }

        return $app->json($user);
    });

Streaming
---------

It's possible to create a streaming response, which is important in cases when
you cannot buffer the data being sent::

    $app->get('/images/{file}', function ($file) use ($app) {
        if (!file_exists(__DIR__.'/images/'.$file)) {
            return $app->abort(404, 'The image was not found.');
        }

        $stream = function () use ($file) {
            readfile($file);
        };

        return $app->stream($stream, 200, array('Content-Type' => 'image/png'));
    });

If you need to send chunks, make sure you call ``ob_flush`` and ``flush``
after every chunk::

    $stream = function () {
        $fh = fopen('http://www.example.com/', 'rb');
        while (!feof($fh)) {
          echo fread($fh, 1024);
          ob_flush();
          flush();
        }
        fclose($fh);
    };

Traits
------

Silex comes with PHP traits that define shortcut methods.

.. caution::

    You need to use PHP 5.4 or later to benefit from this feature.

Almost all built-in service providers have some corresponding PHP traits. To
use them, define your own Application class and include the traits you want::

    use Silex\Application;

    class MyApplication extends Application
    {
        use Application\TwigTrait;
        use Application\SecurityTrait;
        use Application\FormTrait;
        use Application\UrlGeneratorTrait;
        use Application\SwiftmailerTrait;
        use Application\MonologTrait;
        use Application\TranslationTrait;
    }

You can also define your own Route class and use some traits::

    use Silex\Route;

    class MyRoute extends Route
    {
        use Route\SecurityTrait;
    }

To use your newly defined route, override the ``$app['route_class']``
setting::

    $app['route_class'] = 'MyRoute';

Read each provider chapter to learn more about the added methods.

Security
--------

Make sure to protect your application against attacks.

Escaping
~~~~~~~~

When outputting any user input (either route variables GET/POST variables
obtained from the request), you will have to make sure to escape it correctly,
to prevent Cross-Site-Scripting attacks.

* **Escaping HTML**: PHP provides the ``htmlspecialchars`` function for this.
  Silex provides a shortcut ``escape`` method::

      $app->get('/name', function (Silex\Application $app) {
          $name = $app['request']->get('name');
          return "You provided the name {$app->escape($name)}.";
      });

  If you use the Twig template engine you should use its escaping or even
  auto-escaping mechanisms.

* **Escaping JSON**: If you want to provide data in JSON format you should
  use the Silex ``json`` function::

      $app->get('/name.json', function (Silex\Application $app) {
          $name = $app['request']->get('name');
          return $app->json(array('name' => $name));
      });

.. _download: http://silex.sensiolabs.org/download
