WebProfilerServiceProvider
=======================

The Silex Web Profiler service provider allows you to use the wonderful Symfony
web debug toolbar and the Symfony profiler in your Silex application.

Parameters
----------

* **profiler.cache_dir**: Path to the directory for caching.
* **profiler.mount_prefix**: Mount prefix for the router. Defaults to */_profiler*.
* **code.file_link_format**: ?
* **profiler.request_matcher**: ? 
* **profiler.only_exceptions**: ?
* **profiler.only_master_requests**: ?
* **web_profiler.debug_toolbar.enable**: Enable or disable the toolbar. Defaults to *true*.
* **web_profiler.debug_toolbar.position**: Position of the toolbar. Defaults to *bottom*.
* **web_profiler.debug_toolbar.intercept_redirects**: ?

Services
--------

* **stopwatch**: A stopwatch that can be used to track time and memory usage.
* **profiler**: ?
* **profiler.listener**: ?
* **profiler.storage**: ?
* **web_profiler.toolbar.listener**: ?
* **web_profiler.controller.profiler**: ?
* **web_profiler.controller.router**: ?
* **web_profiler.controller.exception**: ?
* **data_collectors**: An associative array of datacollectors.
* **data_collector.templates**: An associative array of twig template paths for the data collectors.
* **data_collectors.form.extractor**: Used to extract data from the forms.

Registering
-----------

Before registering this service provider, you must register *ServiceControllerServiceProvider*, 
*TwigServiceProvider*, *UrlGeneratorServiceProvider*

.. code-block:: php

    $app->register(new Silex\Provider\ServiceControllerServiceProvider());
    $app->register(new Silex\Provider\TwigServiceProvider());
    $app->register(new Silex\Provider\UrlGeneratorServiceProvider());
    $app->register(new Silex\Provider\WebProfilerServiceProvider());

.. note::

    WebProfilerServiceProvider does not come with silex. If you are using Composer, add it as a dependency:

    .. code-block:: bash

        composer require silex/web-profiler

.. note::

    The WebProfilerServiceProvider and some its dependencies (optional or not) comes
    with the "fat" Silex archive but not with the regular one. If you are using
    Composer, add them as a dependency:

    .. code-block:: bash

        composer require twig/twig

.. note::

    If you are using *MonologServiceProvider* for logs, you must also add 
    *symfony/monolog-bridge* as a dependency in your composer.json to get the logs in the profiler.

.. tip::

    If you are using *FormServiceProvider*, the *WebProfilerServiceProvider* will detect that and enable the corresponding panels.

.. caution::

    Make sure to register all other required or used service providers before *WebProfilerServiceProvider*

Usage
-----