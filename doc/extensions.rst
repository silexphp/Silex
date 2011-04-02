Extensions
==========

Silex provides a common interface for extensions. These
define services on the application.

Loading extensions
------------------

In order to load and use an extension, you must register it
on the application. ::

    use Acme\GodExtension;

    $app = new Application();

    $app->register(new GodExtension());

You can also provide some parameters as a second argument.

::

    $app->register(new GodExtension(), array(
        'god.deity' => 'thor',
    ));

Included extensions
-------------------

There are a few extensions that you get out of the box.
All of these are within the ``Silex\Extension`` namespace.

* :doc:`MonologExtension <extensions/monolog>`
* :doc:`TwigExtension <extensions/twig>`

Creating an extension
---------------------

Extensions must implement the ``Silex\ExtensionInterface``.

::

    interface ExtensionInterface
    {
        function register(Application $app);
    }

This is very straight forward, just create a new class that
implements the ``register`` method.  In this method you must
define services on the application which then may make use
of other services and parameters.

Here is an example of such an extension::

    namespace Acme;

    use Silex\ExtensionInterface;

    class HelloExtension implements ExtensionInterface
    {
        public function register(Application $app)
        {
            $app['hello'] = $app->protect(function($name) use ($app) {
                $default = ($app['hello.default_name']) ? $app['hello.default_name'] : '';
                $name = $name ?: $default;
                return "Hello $name";
            });
        }
    }

This class provides a ``hello`` service which is a protected
closure. It takes a name argument and will return
``hello.default_name`` if no name is given. If the default
is also missing, it will use an empty string.

You can now use this extension as follows::

    use Acme\HelloExtension;

    $app = new Application();

    $app->register(new HelloExtension(), array(
        'hello.default_name' => 'Igor',
    ));

    $app->get('/hello', function() use ($app) {
        $name = $app['request']->get('name');
        return $app['hello']($name);
    });

In this example we are getting the ``name`` parameter from the
query string, so the request path would have to be ``/hello?name=Fabien``.

Class loading
~~~~~~~~~~~~~

Extensions are great for tying in external libraries as you
can see by looking at the ``MonologExtension`` and
``TwigExtension``. If the library is decent and follows the
`PSR-0 Naming Standard <http://groups.google.com/group/php-standards/web/psr-0-final-proposal>`_
or the PEAR Naming Convention, it is possible to autoload
classes using the ``UniversalClassLoader``.

As described in the *Services* chapter, there is an
*autoloader* service that you can use for this.

Here is an example of how to use it::

    namespace Acme;

    use Silex\ExtensionInterface;

    class GodExtension implements ExtensionInterface
    {
        public function register(Application $app)
        {
            $app['god'] = $app->share(function() { ... });

            if (isset($app['god.class_path'])) {
                $app['autoloader']->registerPrefix('God_', $app['god.class_path']);
            }
        }
    }

This allows you to simply provide the class  path as an
option when registering the extension::

    $app->register(new GodExtension(), array(
        'god.class_path' => __DIR__.'/vendor/god/src',
    ));
