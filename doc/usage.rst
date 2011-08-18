Usage
=====

This chapter describes how to use Silex.

Bootstrap
---------

To include the Silex all you need to do is require the ``silex.phar``
file and create an instance of ``Silex\Application``. After your
controller definitions, call the ``run`` method on your application::

    require_once __DIR__.'/silex.phar';

    $app = new Silex\Application();

    // definitions

    $app->run();

One other thing you have to do is configure your web server. If you
are using apache you can use a ``.htaccess`` file for this.

.. code-block:: apache

    <IfModule mod_rewrite.c>
        Options -MultiViews

        RewriteEngine On
        #RewriteBase /path/to/app
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [L]
    </IfModule>

.. note::

    If your site is not at the webroot level you will have to uncomment the
    ``RewriteBase`` statement and adjust the path to point to your directory,
    relative from the webroot.

.. tip::

    When developing a website, you might want to turn on the debug mode to
    ease debugging::

        $app['debug'] = true;

Routing
-------

In Silex you define a route and the controller that is called when that
route is matched

A route pattern consists of:

* *Pattern*: The route pattern defines a path that points to a resource.
  The pattern can include variable parts and you are able to set
  RegExp requirements for them.

* *Method*: One of the following HTTP methods: ``GET``, ``POST``, ``PUT``
  ``DELETE``. This describes the interaction with the resource. Commonly
  only ``GET`` and ``POST`` are used, but it is possible to use the
  others as well.

The controller is defined using a closure like this::

    function () {
        // do something
    }

Closures are anonymous functions that may import state from outside
of their definition. This is different from globals, because the outer
state does not have to be global. For instance, you could define a
closure in a function and import local variables of that function.

.. note::

    Closures that do not import scope are referred to as lambdas.
    Because in PHP all anonymous functions are instances of the
    ``Closure`` class, we will not make a distinction here.

The return value of the closure becomes the content of the page.

There is also an alternate way for defining controllers using a
class method. The syntax for that is ``ClassName::methodName``.
Static methods are also possible.

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
statement means something different in this context. It tells the
closure to import the $blogPosts variable from the outer scope. This
allows you to use it from within the closure.

Dynamic routing
~~~~~~~~~~~~~~~

Now, you can create another controller for viewing individual blog
posts::

    $app->get('/blog/show/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
        if (!isset($blogPosts[$id])) {
            $app->abort(404, "Post $id does not exist.");
        }

        $post = $blogPosts[$id];

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

This route definition has a variable ``{id}`` part which is passed
to the closure.

When the post does not exist, we are using ``abort()`` to stop the request
early. It actually throws an exception, which we will see how to handle later
on.

Example POST route
~~~~~~~~~~~~~~~~~~

POST routes signify the creation of a resource. An example for this is a
feedback form. We will use `Swift Mailer
<http://swiftmailer.org/>`_ and assume a copy of it to be present in the
``vendor/swiftmailer`` directory::

    require_once __DIR__.'/vendor/swiftmailer/lib/swift_required.php';

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    $app->post('/feedback', function (Request $request) {
        $message = \Swift_Message::newInstance()
            ->setSubject('[YourSite] Feedback')
            ->setFrom(array('noreply@yoursite.com'))
            ->setTo(array('feedback@yoursite.com'))
            ->setBody($request->get('message'));

        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);
        $mailer->send($message);

        return new Response('Thank you for your feedback!', 201);
    });

It is pretty straight forward. We include the Swift Mailer library,
set up a message and send that message.

The current ``request`` is automatically injected by Silex to the Closure
thanks to the type hinting. It is an instance of `Request
<http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/Request.html>`_,
so you can fetch variables using the request's ``get`` method.

Instead of returning a string we are returning an instance of
`Response
<http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/Response.html>`_.
This allows setting an HTTP
status code, in this case it is set to ``201 Created``.

.. note::

    Silex always uses a ``Response`` internally, it converts strings to
    responses with status code ``200 Ok``.

Other methods
~~~~~~~~~~~~~

You can create controllers for most HTTP methods. Just call one of these
methods on your application: ``get``, ``post``, ``put``, ``delete``. You
can also call ``match``, which will match all methods::

    $app->match('/blog', function () {
        ...
    });

You can then restrict the allowed methods via the ``method`` method::

    $app->match('/blog', function () {
        ...
    })
    ->method('PATCH');

.. note::

    The order in which the routes are defined is significant. The first
    matching route will be used, so place more generic routes at the bottom.

Route variables
~~~~~~~~~~~~~~~

As has been show before you can define variable parts in a route like this::

    $app->get('/blog/show/{id}', function ($id) {
        ...
    });

It is also possible to have more than one variable part, just make sure the
closure arguments match the names of the variable parts::

    $app->get('/blog/show/{postId}/{commentId}', function ($postId, $commentId) {
        ...
    });

While it's not suggested, you could also do this (note the switched arguments)::

    $app->get('/blog/show/{postId}/{commentId}', function ($commentId, $postId) {
        ...
    });

You can also ask for the current Request and Application object::

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

Certain extensions (such as ``UrlGenerator``) can make use of named routes.
By default Silex will generate a route name for you, that cannot really be
used. You can give a route a name by calling ``bind`` on the ``Controller``
object that is returned by the routing methods::

    $app->get('/', function () {
        ...
    })
    ->bind('homepage');

    $app->get('/blog/show/{id}', function ($id) {
        ...
    })
    ->bind('blog_post');


.. note::

    It only makes sense to name routes if you use extensions that make use
    of the ``RouteCollection``.

Before and after filters
------------------------

Silex allows you to run code before and after every request. This happens
through before and after filters. All you need to do is pass a closure::

    $app->before(function () {
        // set up
    });

    $app->after(function () {
        // tear down
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

.. note::

    The filters are only run for the "master" Request.

Error handlers
--------------

If some part of your code throws an exception you will want to display
some kind of error page to the user. This is what error handlers do. You
can also use them to do additional things, such as logging.

To register an error handler, pass a closure to the ``error`` method
which takes an ``Exception`` argument and returns a response::

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
            default:
                $message = 'We are sorry, but something went terribly wrong.';
        }

        return new Response($message, $code);
    });

If you want to set up logging you can use a separate error handler for that.
Just make sure you register it before the response error handlers, because
once a response is returned, the following handlers are ignored.

.. note::

    Silex ships with an extension for `Monolog <https://github.com/Seldaek/monolog>`_
    which handles logging of errors. Check out the *Extensions* chapter
    for details.

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

You can redirect to another page by returning a redirect response, which
you can create by calling the ``redirect`` method::

    use Silex\Application;

    $app->get('/', function (Silex\Application $app) {
        return $app->redirect('/hello');
    });

This will redirect from ``/`` to ``/hello``.

Security
--------

Make sure to protect your application against attacks.

Escaping
~~~~~~~~

When outputting any user input (either route variables GET/POST variables
obtained from the request), you will have to make sure to escape it
correctly, to prevent Cross-Site-Scripting attacks.

* **Escaping HTML**: PHP provides the ``htmlspecialchars`` function for this.
  Silex provides a shortcut ``escape`` method::

      $app->get('/name', function (Silex\Application $app) {
          $name = $app['request']->get('name');
          return "You provided the name {$app->escape($name)}.";
      });

  If you use the Twig template engine you should use its escaping or even
  auto-escaping mechanisms.

* **Escaping JSON**: If you want to provide data in JSON format you should
  use the PHP ``json_encode`` function::

      use Symfony\Component\HttpFoundation\Response;

      $app->get('/name.json', function (Silex\Application $app) {
          $name = $app['request']->get('name');
          return new Response(
              json_encode(array('name' => $name)),
              200,
              array('Content-Type' => 'application/json')
          );
      });

Reusing applications
--------------------

To make your applications reusable, return the ``$app`` variable instead of
calling the ``run()`` method::

    // blog.php
    require_once __DIR__.'/silex.phar';

    $app = new Silex\Application();

    // define your blog app
    $app->get('/post/{id}', function ($id) { ... });

    // return the app instance
    return $app;

Running this application can now be done like this::

    $app = require __DIR__.'/blog.php';
    $app->run();

This pattern allows you to easily "mount" this application under any other
one::

    $blog = require __DIR__.'/blog.php';

    $app = new Silex\Application();
    $app->mount('/blog', $blog);

    // define your main app

    $app->run();

Now, blog posts are available under the ``/blog/post/{id}`` route, along side
any other routes you might have defined.

If you mount many applications, you might want to avoid the overhead of
loading them all on each request by using the ``LazyApplication`` wrapper::

    $blog = new Silex\LazyApplication(__DIR__.'/blog.php');

Console
-------

Silex includes a lightweight console for updating to the latest
version.

To find out which version of Silex you are using, invoke ``silex.phar`` on the
command-line with ``version`` as an argument:

.. code-block:: text

    $ php silex.phar version
    Silex version 0a243d3 2011-04-17 14:49:31 +0200

To check that your are using the latest version, run the ``check`` command:

.. code-block:: text

    $ php silex.phar check

To update ``silex.phar`` to the latest version, invoke the ``update``
command:

.. code-block:: text

    $ php silex.phar update

This will automatically download a new ``silex.phar`` from
``silex-project.org`` and replace the existing one.

Pitfalls
--------

There are some things that can go wrong. Here we will try and outline the
most frequent ones.

PHP configuration
~~~~~~~~~~~~~~~~~

Certain PHP distributions have restrictive default Phar settings. Setting
the following may help.

.. code-block:: ini

    phar.readonly = Off
    phar.require_hash = Off

If you are on Suhosin you will also have to set this:

.. code-block:: ini

    suhosin.executor.include.whitelist = phar

Phar-Stub bug
~~~~~~~~~~~~~

Some PHP installations have a bug that throws a ``PharException`` when trying
to include the Phar. It will also tell you that ``Silex\Application`` could not
be found. A workaround is using the following include line::

    require_once 'phar://'.__DIR__.'/silex.phar/autoload.php';

The exact cause of this issue could not be determined yet.

ioncube loader bug
~~~~~~~~~~~~~~~~~~

Ioncube loader is an extension that can decode PHP encoded file. 
Unfortunately, old versions (prior to version 4.0.9) are not working well 
with phar archives.
You must either upgrade Ioncube loader to version 4.0.9 or newer or disable it 
by commenting or removing this line in your php.ini file:

.. code-block:: ini

    zend_extension = /usr/lib/php5/20090626+lfs/ioncube_loader_lin_5.3.so


IIS configuration
-----------------

If you are using the Internet Information Services from Windows, you can use
this sample ``web.config`` file:

.. code-block:: xml

    <?xml version="1.0"?>
    <configuration>
        <system.webServer>
            <defaultDocument>
                <files>
                    <clear />
                    <add value="index.php" />
                </files>
            </defaultDocument>
            <rewrite>
                <rules>
                    <rule name="Silex Front Controller" stopProcessing="true">
                        <match url="^(.*)$" ignoreCase="false" />
                        <conditions logicalGrouping="MatchAll">
                            <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
                        </conditions>
                        <action type="Rewrite" url="index.php" appendQueryString="true" />
                    </rule>
                </rules>
            </rewrite>
        </system.webServer>
    </configuration>
