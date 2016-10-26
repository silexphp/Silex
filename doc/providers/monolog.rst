Monolog
=======

The *MonologServiceProvider* provides a default logging mechanism through
Jordi Boggiano's `Monolog <https://github.com/Seldaek/monolog>`_ library.

It will log requests and errors and allow you to add logging to your
application. This allows you to debug and monitor the behaviour,
even in production.

Parameters
----------

* **monolog.logfile**: File where logs are written to.
* **monolog.bubble**: (optional) Whether the messages that are handled can bubble up the stack or not.
* **monolog.permission**: (optional) File permissions default (null), nothing change.

* **monolog.level** (optional): Level of logging, defaults
  to ``DEBUG``. Must be one of ``Logger::DEBUG``, ``Logger::INFO``,
  ``Logger::WARNING``, ``Logger::ERROR``. ``DEBUG`` will log
  everything, ``INFO`` will log everything except ``DEBUG``,
  etc.

  In addition to the ``Logger::`` constants, it is also possible to supply the
  level in string form, for example: ``"DEBUG"``, ``"INFO"``, ``"WARNING"``,
  ``"ERROR"``.

* **monolog.name** (optional): Name of the monolog channel,
  defaults to ``myapp``.

Services
--------

* **monolog**: The monolog logger instance.

  Example usage::

    $app['monolog']->debug('Testing the Monolog logging.');

* **monolog.listener**: An event listener to log requests, responses and errors.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\MonologServiceProvider(), array(
        'monolog.logfile' => __DIR__.'/development.log',
    ));

.. note::

    Monolog comes with the "fat" Silex archive but not with the regular one.
    If you are using Composer, add it as a dependency:

    .. code-block:: bash

        composer require monolog/monolog

Usage
-----

The MonologServiceProvider provides a ``monolog`` service. You can use it to
add log entries for any logging level through ``debug()``, ``info()``,
``warning()`` and ``error()``::

    use Symfony\Component\HttpFoundation\Response;

    $app->post('/user', function () use ($app) {
        // ...

        $app['monolog']->info(sprintf("User '%s' registered.", $username));

        return new Response('', 201);
    });

Customization
-------------

You can configure Monolog (like adding or changing the handlers) before using
it by extending the ``monolog`` service::

    $app['monolog'] = $app->share($app->extend('monolog', function($monolog, $app) {
        $monolog->pushHandler(...);

        return $monolog;
    }));

By default, all requests, responses and errors are logged by an event listener
registered as a service called `monolog.listener`. You can replace or remove
this service if you want to modify or disable the informations logged.

Traits
------

``Silex\Application\MonologTrait`` adds the following shortcuts:

* **log**: Logs a message.

.. code-block:: php

    $app->log(sprintf("User '%s' registered.", $username));

For more information, check out the `Monolog documentation
<https://github.com/Seldaek/monolog>`_.
