Usage
=====

This chapter describes how to use Silex.

Bootstrap
---------

To include the Silex all you need to do is require the ``silex.phar``
file and create an instance of ``Silex\Application``. After your
controller definitions, call the ``run`` method on your application.

::

    require __DIR__.'/silex.phar';

    use Silex\Application;

    $app = new Application();

    // definitions

    $app->run();

The use statement aliases ``Silex\Application`` to ``Application``.

One other thing you have to do is configure your web server. If you
are using apache you can use a ``.htaccess`` file for this.

.. code-block:: text

    <IfModule mod_rewrite.c>
    	RewriteEngine On
    	#RewriteBase /path/to/app
    	RewriteCond %{REQUEST_FILENAME} !-f
    	RewriteCond %{REQUEST_FILENAME} !-d
    	RewriteRule ^(.*)$ index.php [QSA,L]
    </IfModule>

.. note::

    If your site is not at the webroot level you will have to uncomment the
    ``RewriteBase`` statement and adjust the path to point to your directory,
    relative from the webroot.

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

The controller can be any PHP callable, so either of::

    'functionName'

    array('Class', 'staticMethodName')

    array($object, 'methodName')

But the encouraged way of defining controllers is a closure is this::

    function() {
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

    $app->get('/blog', function() use ($blogPosts) {
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

    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

    $app->get('/blog/show/{id}', function($id) use ($blogPosts) {
        if (!isset($blogPosts[$id])) {
            throw new NotFoundHttpException();
        }

        $post = $blogPosts[$id];

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

This route definition has a variable ``{id}`` part which is passed
to the closure.

As you can see, we are throwing a ``NotFoundHttpException`` if the
post does not exist. We will see how to handle this later on.

Example POST route
~~~~~~~~~~~~~~~~~~

POST routes signify the creation of a resource. An example for this is a
feedback form. We will use `Swift Mailer
<http://swiftmailer.org/>`_ and assume a copy of it to be present in the
``vendor/swiftmailer`` directory.

::

    require_once __DIR__.'/vendor/swiftmailer/lib/swift_required.php';

    use Symfony\Component\HttpFoundation\Response;

    $app->post('/feedback', function() use ($app) {
        $request = $app['request'];

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

The current ``request`` service is retrieved using the array key syntax.
You can find more information about services in the *Services* chapter.
The request is an instance of ``Symfony\Component\HttpFoundation\Request``,
so you can fetch variables using the request's ``get`` method.

Instead of returning a string we are returning an instance of
``Symfony\Component\HttpFoundation\Response``. This allows setting an HTTP
status code, in this case it is set to ``201 Created``.

.. note::

    Silex always uses ``Response`` internally, it converts strings to
    responses with status code ``200 Ok``.

Other methods
~~~~~~~~~~~~~

You can create controllers for most HTTP methods. Just call one of these
methods on your application: ``get``, ``post``, ``put``, ``delete``. You
can also call ``match``, which will match all methods.

::

    $app->put('/blog', function() {
        ...
    });

.. note::

    The order in which the routes are defined is significant. The first
    matching route will be used, so place more generic routes at the bottom.

Route variables
~~~~~~~~~~~~~~~

As has been before you can define variable parts in a route like this::

    $app->get('/blog/show/{id}', function($id) {
        ...
    });

It is also possible to have more than one variable part, just make sure the
closure arguments match the names of the variable parts.

::

    $app->get('/blog/show/{postId}/{commentId}', function($postId, $commentId) {
        ...
    });

While it's not suggested, you could also do this (note the switched arguments)::

    $app->get('/blog/show/{postId}/{commentId}', function($commentId, $postId) {
        ...
    });

In some cases you may want to only match certain expressions. You can define
requirements using regular expressions by calling ``assert`` on the
``Controller`` object, which is returned by the routing methods.

The following will make sure the ``id`` argument is numeric, since ``\d+``
matches any amount of digits::

    $app->get('/blog/show/{id}', function($id) {
        ...
    })
    ->assert('id', '\d+');

You can also chain these calls::

    $app->get('/blog/show/{postId}/{commentId}', function($postId, $commentId) {
        ...
    })
    ->assert('postId', '\d+')
    ->assert('commentId', '\d+');

Named routes
~~~~~~~~~~~~~~~

Certain extensions (such as ``UrlGenerator``) can make use of named routes.
By default Silex will generate a route name for you, that cannot really be
used. You can give a route a name by calling ``bind`` on the ``Controller``
object that is returned by the routing methods.

::

    $app->get('/', function() {
        ...
    })
    ->bind('homepage');

    $app->get('/blog/show/{id}', function($id) {
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

    $app->before(function() {
        // set up
    });

    $app->after(function() {
        // tear down
    });

Error handlers
--------------

If some part of your code throws an exception you will want to display
some kind of error page to the user. This is what error handlers do. You
can also use them to do additional things, such as logging.

To register an error handler, pass a closure to the ``error`` method
which takes an ``Exception`` argument and returns a response::

    use Symfony\Component\HttpFoundation\Response;

    $app->error(function(\Exception $e) {
        return new Response('We are sorry, but something went terribly wrong.', 500);
    });

You can also check for specific errors by using ``instanceof``, and handle
them differently::

    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpKernel\Exception\HttpException;
    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

    $app->error(function(\Exception $e) {
        if ($e instanceof NotFoundHttpException) {
            return new Response('The requested page could not be found.', 404);
        }

        $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
        return new Response('We are sorry, but something went terribly wrong.', $code);
    });

If you want to set up logging you can use a separate error handler for that.
Just make sure you register it before the response error handlers, because
once a response is returned, the following handlers are ignored.

.. note::

    Silex ships with an extension for `Monolog <https://github.com/Seldaek/monolog>`_
    which handles logging of errors. Check out the *Extensions* chapter
    for details.

Redirects
---------

You can redirect to another page by returning a redirect response, which
you can create by calling the ``redirect`` method::

    $app->get('/', function() use ($app) {
        return $app->redirect('/hello');
    });

This will redirect from ``/`` to ``/hello``.
