Changelog
=========

This changelog references all backward incompatibilities as we introduce them:

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
