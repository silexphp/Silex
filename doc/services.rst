Services
========

Silex is not only a microframework. It is also a micro service container.
It does this by extending `Pimple <https://github.com/fabpot/Pimple>`_
which provides service goodness in just 44 NCLOC.

Dependency Injection
--------------------

.. note::

    You can skip this if you already know what Dependency Injection is.

Dependency Injection is a design pattern where you pass dependencies
to services instead of creating them from within the service or
relying on globals. This generally leads to code that is decoupled,
re-usable, flexible and testable.

Here is an example of a class that takes a ``User`` object and stores
it as a file in JSON format::

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

In this simple example the dependency is the ``basePath`` property.
It is passed to the constructor. This means you can create several
independent instances with different base paths. Of course
dependencies do not have to be simple strings. More often they are
in fact other services.

Container
~~~~~~~~~

A DI or service container is responsible for creating and storing
services. It can recursively create dependencies of the requested
services and inject them. It does so lazily, which means a service
is only created when you actually need it.

Most containers are quite complex and are configured through XML
or YAML files.

Pimple is different.

Pimple
------

Pimple is probably the simplest service container out there. It
makes strong use of closures implements the ArrayAccess interface.

We will start off by creating a new instance of Pimple -- and
because ``Silex\Application`` extends ``Pimple`` all of this
applies to Silex as well. ::

    $container = new Pimple();

or ::

    $app = new Silex\Application();

Parameters
~~~~~~~~~~

You can set parameters (which are usually strings) by setting
an array key on the container::

    $app['some_parameter'] = 'value';

The array key can be anything, by convention periods are
used for namespacing. ::

    $app['asset.host'] = 'http://cdn.mysite.com/';

Reading parameter values is possible with the same
syntax. ::

    echo $app['some_parameter'];

Service definitions
~~~~~~~~~~~~~~~~~~~

Defining services is no different than defining parameters.
You just set an array key on the container to be a closure.
However, when you retrieve the service, the closure is executed.
This allows for lazy service creation.

::

    $app['some_service'] = function () {
        return new Service();
    };

And to retrieve the service, use::

    $service = $app['some_service'];

Every time you call ``$app['some_service']``, a new instance
of the service is created.

Shared services
~~~~~~~~~~~~~~~

You may want to use the same instance of a service across all
of your code. In order to do that you can make a *shared*
service. ::

    $app['some_service'] = $app->share(function () {
        return new Service();
    });

This will create the service on first invocation, and then
return the existing instance on any subsequent access.

Access container from closure
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In many cases you will want to access the service container
from within a service definition closure. For example when
fetching services the current service depends on.

Because of this, the container is passed to the closure as
an argument. ::

    $app['some_service'] = function ($app) {
        return new Service($app['some_other_service'], $app['some_service.config']);
    };

Here you can see an example of Dependency Injection.
``some_service`` depends on ``some_other_service`` and
takes ``some_service.config`` as configuration options.
The dependency is only created when ``some_service`` is
accessed, and it is possible to replace either of the
dependencies by simply overriding those definitions.

.. note::

    This also works for shared services.

Protected closures
~~~~~~~~~~~~~~~~~~

Because the container sees closures as factories for
services, it will always execute them when reading them.

In some cases you will however want to store a closure
as a parameter, so that you can fetch it and execute it
yourself -- with your own arguments.

This is why Pimple allows you to protect your closures
from being executed, by using the ``protect`` method.

::

    $app['closure_parameter'] = $app->protect(function ($a, $b) {
        return $a + $b;
    });

    // will not execute the closure
    $add = $app['closure_parameter'];

    // calling it now
    echo $add(2, 3);

Note that protected closures do not get access to
the container.

Core services
-------------

Silex defines a range of services which can be used
or replaced. You probably don't want to mess with most
of them.

* **request**: Contains the current request object,
  which is an instance of `Request
  <http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/Request.html>`_.
  It gives you access to ``GET``, ``POST`` parameters
  and lots more!

  Example usage::

    $id = $app['request']->get('id');

* **autoloader**: This service provides you with a
  `UniversalClassLoader
  <http://api.symfony.com/2.0/Symfony/Component/ClassLoader/UniversalClassLoader.html>`_
  that is already registered. You can register prefixes
  and namespaces on it.

  Example usage, autoloads Twig classes::

    $app['autoloader']->registerPrefix('Twig_', $app['twig.class_path']);

* **routes**: The `RouteCollection
  <http://api.symfony.com/2.0/Symfony/Component/Routing/RouteCollection.html>`_
  that is used internally. You can add, modify, read
  routes.

* **controllers**: The ``Silex\ControllerCollection``
  that is used internally. Check the *Internals*
  chapter for more information.

* **dispatcher**: The `EventDispatcher
  <http://api.symfony.com/2.0/Symfony/Component/EventDispatcher/EventDispatcher.html>`_
  that is used internally. It is the core of the Symfony2
  system and is used quite a bit by Silex.

* **resolver**: The `ControllerResolver
  <http://api.symfony.com/2.0/Symfony/Component/HttpKernel/Controller/ControllerResolver.html>`_
  that is used internally. It takes care of executing the
  controller with the right arguments.

* **kernel**: The `HttpKernel
  <http://api.symfony.com/2.0/Symfony/Component/HttpKernel/HttpKernel.html>`_
  that is used internally. The HttpKernel is the heart of
  Symfony2, it takes a Request as input and returns a
  Response as output.

.. note::

    All of these Silex core services are shared.
