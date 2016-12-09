Providers
=========

Providers allow the developer to reuse parts of an application into another
one. Silex provides two types of providers defined by two interfaces:
``ServiceProviderInterface`` for services and ``ControllerProviderInterface``
for controllers.

Service Providers
-----------------

Loading providers
~~~~~~~~~~~~~~~~~

In order to load and use a service provider, you must register it on the
application::

    $app = new Silex\Application();

    $app->register(new Acme\DatabaseServiceProvider());

You can also provide some parameters as a second argument. These will be set
**after** the provider is registered, but **before** it is booted::

    $app->register(new Acme\DatabaseServiceProvider(), array(
        'database.dsn'      => 'mysql:host=localhost;dbname=myapp',
        'database.user'     => 'root',
        'database.password' => 'secret_root_password',
    ));

Conventions
~~~~~~~~~~~

You need to watch out in what order you do certain things when interacting
with providers. Just keep these rules in mind:

* Overriding existing services must occur **after** the provider is
  registered.

  *Reason: If the service already exists, the provider will overwrite it.*

* You can set parameters any time **after** the provider is registered, but
  **before** the service is accessed.

  *Reason: Providers can set default values for parameters. Just like with
  services, the provider will overwrite existing values.*

Included providers
~~~~~~~~~~~~~~~~~~

There are a few providers that you get out of the box. All of these are within
the ``Silex\Provider`` namespace:

* :doc:`AssetServiceProvider <providers/asset>`
* :doc:`CsrfServiceProvider <providers/csrf>`
* :doc:`DoctrineServiceProvider <providers/doctrine>`
* :doc:`FormServiceProvider <providers/form>`
* :doc:`HttpCacheServiceProvider <providers/http_cache>`
* :doc:`HttpFragmentServiceProvider <providers/http_fragment>`
* :doc:`LocaleServiceProvider <providers/locale>`
* :doc:`MonologServiceProvider <providers/monolog>`
* :doc:`RememberMeServiceProvider <providers/remember_me>`
* :doc:`SecurityServiceProvider <providers/security>`
* :doc:`SerializerServiceProvider <providers/serializer>`
* :doc:`ServiceControllerServiceProvider <providers/service_controller>`
* :doc:`SessionServiceProvider <providers/session>`
* :doc:`SwiftmailerServiceProvider <providers/swiftmailer>`
* :doc:`TranslationServiceProvider <providers/translation>`
* :doc:`TwigServiceProvider <providers/twig>`
* :doc:`ValidatorServiceProvider <providers/validator>`
* :doc:`VarDumperServiceProvider <providers/var_dumper>`

.. note::

    The Silex core team maintains a `WebProfiler
    <https://github.com/silexphp/Silex-WebProfiler>`_ provider that helps debug
    code in the development environment thanks to the Symfony web debug toolbar
    and the Symfony profiler.

Third party providers
~~~~~~~~~~~~~~~~~~~~~

Some service providers are developed by the community. Those third-party
providers are listed on `Silex' repository wiki
<https://github.com/silexphp/Silex/wiki/Third-Party-ServiceProviders-for-Silex-2.x>`_.

You are encouraged to share yours.

Creating a provider
~~~~~~~~~~~~~~~~~~~

Providers must implement the ``Pimple\ServiceProviderInterface``::

    interface ServiceProviderInterface
    {
        public function register(Container $container);
    }

This is very straight forward, just create a new class that implements the
register method. In the ``register()`` method, you can define services on the
application which then may make use of other services and parameters.

.. tip::

    The ``Pimple\ServiceProviderInterface`` belongs to the Pimple package, so
    take care to only use the API of ``Pimple\Container`` within your
    ``register`` method. Not only is this a good practice due to the way Pimple
    and Silex work, but may allow your provider to be used outside of Silex.

Optionally, your service provider can implement the
``Silex\Api\BootableProviderInterface``. A bootable provider must
implement the ``boot()`` method, with which you can configure the application, just
before it handles a request::

    interface BootableProviderInterface
    {
        function boot(Application $app);
    }

Another optional interface, is the ``Silex\Api\EventListenerProviderInterface``.
This interface contains the ``subscribe()`` method, which allows your provider to
subscribe event listener with Silex's EventDispatcher, just before it handles a
request::

    interface EventListenerProviderInterface
    {
        function subscribe(Container $app, EventDispatcherInterface $dispatcher);
    }

Here is an example of such a provider::

    namespace Acme;

    use Pimple\Container;
    use Pimple\ServiceProviderInterface;
    use Silex\Application;
    use Silex\Api\BootableProviderInterface;
    use Silex\Api\EventListenerProviderInterface;
    use Symfony\Component\EventDispatcher\EventDispatcherInterface;
    use Symfony\Component\HttpKernel\KernelEvents;
    use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

    class HelloServiceProvider implements ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface
    {
        public function register(Container $app)
        {
            $app['hello'] = $app->protect(function ($name) use ($app) {
                $default = $app['hello.default_name'] ? $app['hello.default_name'] : '';
                $name = $name ?: $default;

                return 'Hello '.$app->escape($name);
            });
        }

        public function boot(Application $app)
        {
            // do something
        }

        public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
        {
            $dispatcher->addListener(KernelEvents::REQUEST, function(FilterResponseEvent $event) use ($app) {
                // do something
            });
        }
    }

This class provides a ``hello`` service which is a protected closure. It takes
a ``name`` argument and will return ``hello.default_name`` if no name is
given. If the default is also missing, it will use an empty string.

You can now use this provider as follows::

    use Symfony\Component\HttpFoundation\Request;

    $app = new Silex\Application();

    $app->register(new Acme\HelloServiceProvider(), array(
        'hello.default_name' => 'Igor',
    ));

    $app->get('/hello', function (Request $request) use ($app) {
        $name = $request->get('name');

        return $app['hello']($name);
    });

In this example we are getting the ``name`` parameter from the query string,
so the request path would have to be ``/hello?name=Fabien``.

.. _controller-providers:

Controller Providers
--------------------

Loading providers
~~~~~~~~~~~~~~~~~

In order to load and use a controller provider, you must "mount" its
controllers under a path::

    $app = new Silex\Application();

    $app->mount('/blog', new Acme\BlogControllerProvider());

All controllers defined by the provider will now be available under the
``/blog`` path.

Creating a provider
~~~~~~~~~~~~~~~~~~~

Providers must implement the ``Silex\Api\ControllerProviderInterface``::

    interface ControllerProviderInterface
    {
        public function connect(Application $app);
    }

Here is an example of such a provider::

    namespace Acme;

    use Silex\Application;
    use Silex\Api\ControllerProviderInterface;

    class HelloControllerProvider implements ControllerProviderInterface
    {
        public function connect(Application $app)
        {
            // creates a new controller based on the default route
            $controllers = $app['controllers_factory'];

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

You can use this provider as follows::

    $app = new Silex\Application();

    $app->mount('/blog', new Acme\HelloControllerProvider());

In this example, the ``/blog/`` path now references the controller defined in
the provider.

.. tip::

    You can also define a provider that implements both the service and the
    controller provider interface and package in the same class the services
    needed to make your controllers work.
