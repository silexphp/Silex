PSR-11
======

The *Psr11ServiceProvider* provides a container for accessing your application
services using the PSR-11 ``ContainerInterface`` interface.

PSR-11 is a standard that describes a common interface for dependency injection
containers. Using the PSR-11 container will allow you to:

* Use objects that expect a ``ContainerInterface`` instance.

* Decouple your own code from the ``Silex\Application`` class (something that
  could prove useful if you intend to port your application to the Symfony
  full-stack framework in the future).

Services
--------

* **container**: A container that implements `ContainerInterface
  <https://github.com/container-interop/fig-standards/blob/master/proposed/container.md>`_
  and give you access to all your services.

  Example usage::

    $container = $app['container'];
    $service = $container->get('service');

* **service_locator.factory**: A factory that creates `PSR-11 service locators
  <https://github.com/silexphp/Pimple/blob/master/README.rst#using-the-psr-11-servicelocator>`_.

  Example usage::

    $locator = $app['service_locator.factory'](array('logger', 'dispatcher', 'form.factory'));
    $service = new MyService($locator);

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\Psr11ServiceProvider());

Using the Container in Controllers
----------------------------------

The provider registers an argument value resolver that can resolve controller
arguments type-hinted with ``Psr\Container\ContainerInterface``.

.. code-block:: php

    use Psr\Container\ContainerInterface;

    $app->get('/', function (ContainerInterface $container) {
        $container->get('monolog')->debug('Showing the homepage.');
    });

Using Service Locators
----------------------

Injecting the entire service container to get only the services you need is
not recommended because it gives objects a too broad access to the rest of
the application and it hides their actual dependencies.

Instead, you should consider using a service locator. It will give your
objects access to a set of predefined services while instantiating them only
when actually needed:

.. code-block:: php

    use Psr\Container\ContainerInterface;

    class MyService
    {
        private $container;

        public function __construct(ContainerInterface $container)
        {
            $this->container = $container;
        }

        public function processFoo()
        {
            $this->container->get('foo')->process();
        }

        public function processBar()
        {
            $this->container->get('bar')->process();
        }
    }

    $app['service'] = function ($app) {
        return new MyService($app['service_locator.factory'](array('foo', 'bar')));
    };

You can also inject a ``ServiceLocator`` instance into your controllers
instead of the whole container by using the ``convert()`` function:

.. code-block:: php

    $app->get('/', function (ContainerInterface $container) {
        // do something with the foo and the bar services
    })->convert('container', function () use ($app) {
        return $app['service_locator.factory'](array('foo', 'bar'));
    });
