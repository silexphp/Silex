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

Monolog
~~~~~~~

The *MonologExtension* provides a default logging mechanism
through Jordi Boggiano's `Monolog <https://github.com/Seldaek/monolog>`_
library.

It will log requests and errors and allow you to add debug
logging to your application, so you don't have to use
``var_dump`` so much anymore. You can use the grown-up
version called ``tail -f``.

**Parameters**

* **monolog.logfile**: File where logs are written to.

* **monolog.class_path** (optional): Path to where the
  Monolog library is located.

* **monolog.level** (optional): Level of logging defaults
  to ``DEBUG``. Must be one of ``Logger::DEBUG``, ``Logger::INFO``,
  ``Logger::WARNING``, ``Logger::ERROR``. ``DEBUG`` will log
  everything, ``INFO`` will log everything except ``DEBUG``,
  etc.

* **monolog.name** (optional): Name of the monolog channel,
  defaults to ``myapp``.

**Services**

* **monolog**: The monolog logger instance.

  Example usage::

    $app['monolog']->addDebug('Testing the Monolog logging.');

* **monolog.configure**: Protected closure that takes the
  logger as an argument. You can override it if you do not
  want the default behavior.

**Registering**

Make sure you place a copy of *Monolog* in the ``vendor/monolog``
directory.

::

    use Silex\Extension\MonologExtension;

    $app->register(new MonologExtension(), array(
        'monolog.logfile'       => __DIR__.'/development.log',
        'monolog.class_path'    => __DIR__.'/vendor/monolog/src',
    ));

Twig
~~~~

The *TwigExtension* provides integration with the `Twig
<http://www.twig-project.org/>`_ template engine.

**Parameters**

* **twig.path**: Path to the directory containing twig template
  files.

* **twig.templates** (optional): If this option is provided
  you don't have to provide a ``twig.path``. It is an
  associative array of template names to template contents.
  Use this if you want to define your templates inline.

* **twig.options** (optional): An associative array of twig
  options. Check out the twig documentation for more information.

* **twig.class_path** (optional): Path to where the Twig
  library is located.

* **symfony_bridges** (optional): Set this to true if you want
  to integrate the ``UrlGeneratorExtension`` and the
  ``TranslationExtension`` with Twig. This requires loading
  Symfony2 ``Bridge`` classes which include those Twig extensions.

**Services**

* **twig**: The ``Twig_Environment`` instance, you will only
  need to use this.

* **twig.configure**: Protected closure that takes the twig
  environment as an argument. You can use it to add more
  custom globals.

* **twig.loader**: The loader for twig templates which uses
  the ``twig.path`` and the ``twig.templates`` options. You
  can also replace the loader completely.

**Registering**

Make sure you place a copy of *Twig* in the ``vendor/twig``
directory.

::

    use Silex\Extension\TwigExtension;

    $app->register(new TwigExtension(), array(
        'twig.path'       => __DIR__.'/views',
        'twig.class_path' => __DIR__.'/vendor/twig/lib',
    ));

**Usage**

::

    $app->get('/hello/{name}', function($name) use ($app) {
        return $app['twig']->render('hello.twig', array(
            'name' => $name,
        ));
    });

This will render a file named ``views/hello.twig``.

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
