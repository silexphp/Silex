Services
========

Silex is not only a framework, it is also a service container. It does this by
extending `Pimple <http://pimple.sensiolabs.org>`_ which provides a very simple
service container.

Dependency Injection
--------------------

.. note::

    You can skip this if you already know what Dependency Injection is.

Dependency Injection is a design pattern where you pass dependencies to
services instead of creating them from within the service or relying on
globals. This generally leads to code that is decoupled, re-usable, flexible
and testable.

Here is an example of a class that takes a ``User`` object and stores it as a
file in JSON format::

    class JsonUserPersister
    {
        private $basePath;

        public function __construct($basePath)
        {
            $this->basePath = $basePath;
        }

        public function persist(User $user)
        {
            $data = $user->getAttributes();
            $json = json_encode($data);
            $filename = $this->basePath.'/'.$user->id.'.json';
            file_put_contents($filename, $json, LOCK_EX);
        }
    }

In this simple example the dependency is the ``basePath`` property. It is
passed to the constructor. This means you can create several independent
instances with different base paths. Of course dependencies do not have to be
simple strings. More often they are in fact other services.

A service container is responsible for creating and storing services. It can
recursively create dependencies of the requested services and inject them. It
does so lazily, which means a service is only created when you actually need it.

Pimple
------

Pimple makes strong use of closures and implements the ArrayAccess interface.

We will start off by creating a new instance of Pimple -- and because
``Silex\Application`` extends ``Pimple\Container`` all of this applies to Silex
as well::

    $container = new Pimple\Container();

or::

    $app = new Silex\Application();

Parameters
~~~~~~~~~~

You can set parameters (which are usually strings) by setting an array key on
the container::

    $app['some_parameter'] = 'value';

The array key can be any value. By convention dots are used for namespacing::

    $app['asset.host'] = 'http://cdn.mysite.com/';

Reading parameter values is possible with the same syntax::

    echo $app['some_parameter'];

Service definitions
~~~~~~~~~~~~~~~~~~~

Defining services is no different than defining parameters. You just set an
array key on the container to be a closure. However, when you retrieve the
service, the closure is executed. This allows for lazy service creation::

    $app['some_service'] = function () {
        return new Service();
    };

And to retrieve the service, use::

    $service = $app['some_service'];

On first invocation, this will create the service; the same instance will then
be returned on any subsequent access.

Factory services
~~~~~~~~~~~~~~~~

If you want a different instance to be returned for each service access, wrap
the service definition with the ``factory()`` method::

    $app['some_service'] = $app->factory(function () {
        return new Service();
    });

Every time you call ``$app['some_service']``, a new instance of the service is
created.

Access container from closure
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In many cases you will want to access the service container from within a
service definition closure. For example when fetching services the current
service depends on.

Because of this, the container is passed to the closure as an argument::

    $app['some_service'] = function ($app) {
        return new Service($app['some_other_service'], $app['some_service.config']);
    };

Here you can see an example of Dependency Injection. ``some_service`` depends
on ``some_other_service`` and takes ``some_service.config`` as configuration
options. The dependency is only created when ``some_service`` is accessed, and
it is possible to replace either of the dependencies by simply overriding
those definitions.

Going back to our initial example, here's how we could use the container
to manage its dependencies::

    $app['user.persist_path'] = '/tmp/users';
    $app['user.persister'] = function ($app) {
        return new JsonUserPersister($app['user.persist_path']);
    };


Protected closures
~~~~~~~~~~~~~~~~~~

Because the container sees closures as factories for services, it will always
execute them when reading them.

In some cases you will however want to store a closure as a parameter, so that
you can fetch it and execute it yourself -- with your own arguments.

This is why Pimple allows you to protect your closures from being executed, by
using the ``protect`` method::

    $app['closure_parameter'] = $app->protect(function ($a, $b) {
        return $a + $b;
    });

    // will not execute the closure
    $add = $app['closure_parameter'];

    // calling it now
    echo $add(2, 3);

Note that protected closures do not get access to the container.

Core services
-------------

Silex defines a range of services.

* **request_stack**: Controls the lifecycle of requests, an instance of
  `RequestStack <http://api.symfony.com/master/Symfony/Component/HttpFoundation/RequestStack.html>`_.
  It gives you access to ``GET``, ``POST`` parameters and lots more!

  Example usage::

    $id = $app['request_stack']->getCurrentRequest()->get('id');

  A request is only available when a request is being served; you can only
  access it from within a controller, an application before/after middlewares,
  or an error handler.

* **routes**: The `RouteCollection
  <http://api.symfony.com/master/Symfony/Component/Routing/RouteCollection.html>`_
  that is used internally. You can add, modify, read routes.

* **url_generator**: An instance of `UrlGenerator
  <http://api.symfony.com/master/Symfony/Component/Routing/Generator/UrlGenerator.html>`_,
  using the `RouteCollection
  <http://api.symfony.com/master/Symfony/Component/Routing/RouteCollection.html>`_
  that is provided through the ``routes`` service. It has a ``generate``
  method, which takes the route name as an argument, followed by an array of
  route parameters.

* **controllers**: The ``Silex\ControllerCollection`` that is used internally.
  Check the :doc:`Internals chapter <internals>` for more information.

* **dispatcher**: The `EventDispatcher
  <http://api.symfony.com/master/Symfony/Component/EventDispatcher/EventDispatcher.html>`_
  that is used internally. It is the core of the Symfony system and is used
  quite a bit by Silex.

* **resolver**: The `ControllerResolver
  <http://api.symfony.com/master/Symfony/Component/HttpKernel/Controller/ControllerResolver.html>`_
  that is used internally. It takes care of executing the controller with the
  right arguments.

* **kernel**: The `HttpKernel
  <http://api.symfony.com/master/Symfony/Component/HttpKernel/HttpKernel.html>`_
  that is used internally. The HttpKernel is the heart of Symfony, it takes a
  Request as input and returns a Response as output.

* **request_context**: The request context is a simplified representation of
  the request that is used by the router and the URL generator.

* **exception_handler**: The Exception handler is the default handler that is
  used when you don't register one via the ``error()`` method or if your
  handler does not return a Response. Disable it with
  ``unset($app['exception_handler'])``.

* **logger**: A `LoggerInterface <https://github.com/php-fig/log/blob/master/Psr/Log/LoggerInterface.php>`_ instance. By default, logging is
  disabled as the value is set to ``null``. To enable logging you can either use
  the :doc:`MonologServiceProvider <providers/monolog>` or define your own ``logger`` service that
  conforms to the PSR logger interface.

Core traits
-----------

* ``Silex\Application\UrlGeneratorTrait`` adds the following shortcuts:

  * **path**: Generates a path.

  * **url**: Generates an absolute URL.

  .. code-block:: php

      $app->path('homepage');
      $app->url('homepage');

Core parameters
---------------

* **request.http_port** (optional): Allows you to override the default port
  for non-HTTPS URLs. If the current request is HTTP, it will always use the
  current port.

  Defaults to 80.

  This parameter can be used when generating URLs.

* **request.https_port** (optional): Allows you to override the default port
  for HTTPS URLs. If the current request is HTTPS, it will always use the
  current port.

  Defaults to 443.

  This parameter can be used when generating URLs.

* **debug** (optional): Returns whether or not the application is running in
  debug mode.

  Defaults to false.

* **charset** (optional): The charset to use for Responses.

  Defaults to UTF-8.
