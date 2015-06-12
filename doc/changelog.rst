Changelog
=========

2.0.0 (2014-XX-XX)
------------------

* bumped minimum version of PHP to 5.5.0
* Updated Pimple to 3.0
* Updated session listeners to extends HttpKernel ones
* [BC BREAK] Locale management has been moved to LocaleServiceProvider which must be registered
  if you want Silex to manage your locale (must also be registered for the translation service provider)
* [BC BREAK] Provider interfaces moved to Silex\Api namespace, published as
  separate package via subtree split
* [BC BREAK] ServiceProviderInterface split in to EventListenerProviderInterface
  and BootableProviderInterface
* [BC BREAK] Service Provider support files moved under Silex\Provider
  namespace, allowing publishing as separate package via sub-tree split
* ``monolog.exception.logger_filter`` option added to Monolog service provider
* [BC BREAK] ``$app['request']`` service removed, use ``$app['request_stack']`` instead

1.3.1 (2015-XX-XX)
------------------

* n/a

1.3.0 (2015-06-05)
------------------

* added a `$app['user']` to get the current user (security provider)
* added view handlers
* added support for the OPTIONS HTTP method
* added caching for the Translator provider
* deprecated `$app['exception_handler']->disable()` in favor of `unset($app['exception_handler'])`
* made Silex compatible with Symfony 2.7 an 2.8 (and keep compatibility with Symfony 2.3, 2.5, and 2.6)
* removed deprecated TwigCoreExtension class (register the new HttpFragmentServiceProvider instead)
* bumped minimum version of PHP to 5.3.9

1.2.5 (2015-06-04)
------------------

* no code changes (last version of the 1.2 branch)

1.2.4 (2015-04-11)
------------------

* fixed the exception message when mounting a collection that doesn't return a ControllerCollection
* fixed Symfony dependencies (Silex 1.2 is not compatible with Symfony 2.7)

1.2.3 (2015-01-20)
------------------

* fixed remember me listener
* fixed translation files loading when they do not exist
* allowed global after middlewares to return responses like route specific ones

1.2.2 (2014-09-26)
------------------

* fixed Translator locale management
* added support for the $app argument in application middlewares (to make it consistent with route middlewares)
* added form.types to the Form provider

1.2.1 (2014-07-01)
------------------

* added support permissions in the Monolog provider
* fixed Switfmailer spool where the event dispatcher is different from the other ones
* fixed locale when changing it on the translator itself

1.2.0 (2014-03-29)
------------------

* Allowed disabling the boot logic of MonologServiceProvider
* Reverted "convert attributes on the request that actually exist"
* [BC BREAK] Routes are now always added in the order of their registration (even for mounted routes)
* Added run() on Route to be able to define the controller code
* Deprecated TwigCoreExtension (register the new HttpFragmentServiceProvider instead)
* Added HttpFragmentServiceProvider
* Allowed a callback to be a method call on a service (before, after, finish, error, on Application; convert, before, after on Controller)

1.1.3 (2013-XX-XX)
------------------

* Fixed translator locale management

1.1.2 (2013-10-30)
------------------

* Added missing "security.hide_user_not_found" support in SecurityServiceProvider
* Fixed event listeners that are registered after the boot via the on() method

1.0.2 (2013-10-30)
------------------

* Fixed SecurityServiceProvider to use null as a fake controller so that routes can be dumped

1.1.1 (2013-10-11)
------------------

* Removed or replaced deprecated Symfony code
* Updated code to take advantages of 2.3 new features
* Only convert attributes on the request that actually exist.

1.1.0 (2013-07-04)
------------------

* Support for any ``Psr\Log\LoggerInterface`` as opposed to the monolog-bridge
  one.
* Made dispatcher proxy methods ``on``, ``before``, ``after`` and ``error``
  lazy, so that they will not instantiate the dispatcher early.
* Dropped support for 2.1 and 2.2 versions of Symfony.

1.0.1 (2013-07-04)
------------------

* Fixed RedirectableUrlMatcher::redirect() when Silex is configured to use a logger
* Make ``DoctrineServiceProvider`` multi-db support lazy.

1.0.0 (2013-05-03)
------------------

* **2013-04-12**: Added support for validators as services.

* **2013-04-01**: Added support for host matching with symfony 2.2::

      $app->match('/', function() {
          // app-specific action
      })->host('example.com');

      $app->match('/', function ($user) {
          // user-specific action
      })->host('{user}.example.com');

* **2013-03-08**: Added support for form type extensions and guessers as
  services.

* **2013-03-08**: Added support for remember-me via the
  ``RememberMeServiceProvider``.

* **2013-02-07**: Added ``Application::sendFile()`` to ease sending
  ``BinaryFileResponse``.

* **2012-11-05**: Filters have been renamed to application middlewares in the
  documentation.

* **2012-11-05**: The ``before()``, ``after()``, ``error()``, and ``finish()``
  listener priorities now set the priority of the underlying Symfony event
  instead of a custom one before.

* **2012-11-05**: Removing the default exception handler should now be done
  via its ``disable()`` method:

    Before:

        unset($app['exception_handler']);

    After:

        $app['exception_handler']->disable();

* **2012-07-15**: removed the ``monolog.configure`` service. Use the
  ``extend`` method instead:

    Before::

        $app['monolog.configure'] = $app->protect(function ($monolog) use ($app) {
            // do something
        });

    After::

        $app['monolog'] = $app->share($app->extend('monolog', function($monolog, $app) {
            // do something

            return $monolog;
        }));


* **2012-06-17**: ``ControllerCollection`` now takes a required route instance
  as a constructor argument.

    Before::

        $controllers = new ControllerCollection();

    After::

        $controllers = new ControllerCollection(new Route());

        // or even better
        $controllers = $app['controllers_factory'];

* **2012-06-17**: added application traits for PHP 5.4

* **2012-06-16**: renamed ``request.default_locale`` to ``locale``

* **2012-06-16**: Removed the ``translator.loader`` service. See documentation
  for how to use XLIFF or YAML-based translation files.

* **2012-06-15**: removed the ``twig.configure`` service. Use the ``extend``
  method instead:

    Before::

        $app['twig.configure'] = $app->protect(function ($twig) use ($app) {
            // do something
        });

    After::

        $app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
            // do something

            return $twig;
        }));

* **2012-06-13**: Added a route ``before`` middleware

* **2012-06-13**: Renamed the route ``middleware`` to ``before``

* **2012-06-13**: Added an extension for the Symfony Security component

* **2012-05-31**: Made the ``BrowserKit``, ``CssSelector``, ``DomCrawler``,
  ``Finder`` and ``Process`` components optional dependencies. Projects that
  depend on them (e.g. through functional tests) should add those dependencies
  to their ``composer.json``.

* **2012-05-26**: added ``boot()`` to ``ServiceProviderInterface``.

* **2012-05-26**: Removed ``SymfonyBridgesServiceProvider``. It is now implicit
  by checking the existence of the bridge.

* **2012-05-26**: Removed the ``translator.messages`` parameter (use
  ``translator.domains`` instead).

* **2012-05-24**: Removed the ``autoloader`` service (use composer instead).
  The ``*.class_path`` settings on all the built-in providers have also been
  removed in favor of Composer.

* **2012-05-21**: Changed error() to allow handling specific exceptions.

* **2012-05-20**: Added a way to define settings on a controller collection.

* **2012-05-20**: The Request instance is not available anymore from the
  Application after it has been handled.

* **2012-04-01**: Added ``finish`` filters.

* **2012-03-20**: Added ``json`` helper::

        $data = array('some' => 'data');
        $response = $app->json($data);

* **2012-03-11**: Added route middlewares.

* **2012-03-02**: Switched to use Composer for dependency management.

* **2012-02-27**: Updated to Symfony 2.1 session handling.

* **2012-01-02**: Introduced support for streaming responses.

* **2011-09-22**: ``ExtensionInterface`` has been renamed to
  ``ServiceProviderInterface``. All built-in extensions have been renamed
  accordingly (for instance, ``Silex\Extension\TwigExtension`` has been
  renamed to ``Silex\Provider\TwigServiceProvider``).

* **2011-09-22**: The way reusable applications work has changed. The
  ``mount()`` method now takes an instance of ``ControllerCollection`` instead
  of an ``Application`` one.

    Before::

        $app = new Application();
        $app->get('/bar', function() { return 'foo'; });

        return $app;

    After::

        $app = new ControllerCollection();
        $app->get('/bar', function() { return 'foo'; });

        return $app;

* **2011-08-08**: The controller method configuration is now done on the Controller itself

    Before::

        $app->match('/', function () { echo 'foo'; }, 'GET|POST');

    After::

        $app->match('/', function () { echo 'foo'; })->method('GET|POST');
