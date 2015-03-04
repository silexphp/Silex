WebProfilerServiceProvider
=======================

The Silex Web Profiler service provider allows you to use the wonderful Symfony
web debug toolbar and the Symfony profiler in your Silex application.

Parameters
----------

* **profiler.cache_dir**: Path to the directory for caching
* **profiler.mount_prefix**: Mount prefix for the router

Services
--------

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\WebProfilerServiceProvider());

.. note::

    WebProfilerServiceProvider does not come with silex. If you are using Composer, add it as a dependency:

    .. code-block:: bash

        composer require silex/web-profiler

Usage
-----