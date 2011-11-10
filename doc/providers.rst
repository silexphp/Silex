Providers
=========

Providers allow the developer to reuse parts of an application into another
one. Silex provides two types of providers defined by two interfaces:
`ServiceProviderInterface` for services and `ControllerProviderInterface` for
controllers.

Service Providers
-----------------

Loading providers
~~~~~~~~~~~~~~~~~

In order to load and use a service provider, you must register it on the
application::

    $app = new Silex\Application();

    $app->register(new Acme\DatabaseServiceProvider());

You can also provide some parameters as a second argument. These
will be set **before** the provider is registered::

    $app->register(new Acme\DatabaseServiceProvider(), array(
        'database.dsn'      => 'mysql:host=localhost;dbname=myapp',
        'database.user'     => 'root',
        'database.password' => 'secret_root_password',
    ));

Conventions
~~~~~~~~~~~

You need to watch out in what order you do certain things when
interacting with providers. Just keep to these rules:

* Class paths (for the autoloader) must be defined **before**
  the provider is registered. Passing it as a second argument
  to ``Application::register`` qualifies too, because it sets
  the passed parameters first.

  *Reason: The provider will set up the autoloader at
  provider register time. If the class path is not set
  at that point, no autoloader can be registered.*

* Overriding existing services must occur **after** the
  provider is registered.

  *Reason: If the services already exist, the provider
  will overwrite it.*

* You can set parameters any time before the service is
  accessed.

Make sure to stick to this behavior when creating your
own providers.

Included providers
~~~~~~~~~~~~~~~~~~

There are a few provider that you get out of the box.
All of these are within the ``Silex\Provider`` namespace.

* :doc:`DoctrineServiceProvider <providers/doctrine>`
* :doc:`MonologServiceProvider <providers/monolog>`
* :doc:`SessionServiceProvider <providers/session>`
* :doc:`SwiftmailerServiceProvider <providers/swiftmailer>`
* :doc:`SymfonyBridgesServiceProvider <providers/symfony_bridges>`
* :doc:`TwigServiceProvider <providers/twig>`
* :doc:`TranslationServiceProvider <providers/translation>`
* :doc:`UrlGeneratorServiceProvider <providers/url_generator>`
* :doc:`ValidatorServiceProvider <providers/validator>`
* :doc:`HttpCacheServiceProvider <providers/http_cache>`

Creating a provider
~~~~~~~~~~~~~~~~~~~

Providers must implement the ``Silex\ServiceProviderInterface``::

    interface ServiceProviderInterface
    {
        function register(Application $app);
    }

This is very straight forward, just create a new class that
implements the ``register`` method.  In this method you must
define services on the application which then may make use
of other services and parameters.

Here is an example of such a provider::

    namespace Acme;

    use Silex\Application;
    use Silex\ServiceProviderInterface;

    class HelloServiceProvider implements ServiceProviderInterface
    {
        public function register(Application $app)
        {
            $app['hello'] = $app->protect(function ($name) use ($app) {
                $default = $app['hello.default_name'] ? $app['hello.default_name'] : '';
                $name = $name ?: $default;

                return 'Hello '.$app->escape($name);
            });
        }
    }

This class provides a ``hello`` service which is a protected
closure. It takes a ``name`` argument and will return
``hello.default_name`` if no name is given. If the default
is also missing, it will use an empty string.

You can now use this provider as follows::

    $app = new Silex\Application();

    $app->register(new Acme\HelloServiceProvider(), array(
        'hello.default_name' => 'Igor',
    ));

    $app->get('/hello', function () use ($app) {
        $name = $app['request']->get('name');

        return $app['hello']($name);
    });

In this example we are getting the ``name`` parameter from the
query string, so the request path would have to be ``/hello?name=Fabien``.

Class loading
~~~~~~~~~~~~~

Providers are great for tying in external libraries as you
can see by looking at the ``MonologServiceProvider`` and
``TwigServiceProvider``. If the library is decent and follows the
`PSR-0 Naming Standard <http://groups.google.com/group/php-standards/web/psr-0-final-proposal>`_
or the PEAR Naming Convention, it is possible to autoload
classes using the ``UniversalClassLoader``.

As described in the *Services* chapter, there is an
*autoloader* service which can be used for this.

Here is an example of how to use it (based on `Buzz <https://github.com/kriswallsmith/Buzz>`_)::

    namespace Acme;

    use Silex\Application;
    use Silex\ServiceProviderInterface;

    class BuzzServiceProvider implements ServiceProviderInterface
    {
        public function register(Application $app)
        {
            $app['buzz'] = $app->share(function () { ... });

            if (isset($app['buzz.class_path'])) {
                $app['autoloader']->registerNamespace('Buzz', $app['buzz.class_path']);
            }
        }
    }

This allows you to simply provide the class path as an
option when registering the provider::

    $app->register(new BuzzServiceProvider(), array(
        'buzz.class_path' => __DIR__.'/vendor/buzz/lib',
    ));

.. note::

    For libraries that do not use PHP 5.3 namespaces you can use ``registerPrefix``
    instead of ``registerNamespace``, which will use an underscore as directory
    delimiter.

Controllers providers
---------------------

Loading providers
~~~~~~~~~~~~~~~~~

In order to load and use a controller provider, you must "mount" its
controllers under a path::

    $app = new Silex\Application();

    $app->mount('/blog', new Acme\BlogControllerProvider());

All controllers defined by the provider will now be available under the
`/blog` path.

Creating a provider
~~~~~~~~~~~~~~~~~~~

Providers must implement the ``Silex\ControllerProviderInterface``::

    interface ControllerProviderInterface
    {
        function connect(Application $app);
    }

Here is an example of such a provider::

    namespace Acme;

    use Silex\Application;
    use Silex\ControllerProviderInterface;
    use Silex\ControllerCollection;

    class HelloControllerProvider implements ControllerProviderInterface
    {
        public function connect(Application $app)
        {
            $controllers = new ControllerCollection();

            $controllers->get('/', function (Application $app) {
                return $app->redirect('/hello');
            });

            return $controllers;
        }
    }

The ``connect`` method must return an instance of ``ControllerCollection``.
``ControllerCollection`` is the class where all controller related methods are
defined (like ``get``, ``post``, ``match``, ...).

.. tip::

    The ``Application`` class acts in fact as a proxy for these methods.

You can now use this provider as follows::

    $app = new Silex\Application();

    $app->mount('/blog', new Acme\HelloControllerProvider());

In this example, the ``/blog/`` path now references the controller defined in
the provider.

.. tip::

    You can also define a provider that implements both the service and the
    controller provider interface and package in the same class the services
    needed to make your controllers work.
