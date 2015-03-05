WebProfilerServiceProvider
=======================

The Silex Web Profiler service provider allows you to use the wonderful Symfony
web debug toolbar and the Symfony profiler in your Silex application.

Parameters
----------

* **profiler.cache_dir**: Path to the directory for caching.
* **profiler.mount_prefix**: Mount prefix for the router. Defaults to */_profiler*.
.. * **code.file_link_format**: ?
.. * **profiler.request_matcher**: ? 
.. * **profiler.only_exceptions**: ?
.. * **profiler.only_master_requests**: ?
* **web_profiler.debug_toolbar.enable**: Enable or disable the toolbar. Defaults to *true*.
* **web_profiler.debug_toolbar.position**: Position of the toolbar. Defaults to *bottom*.
* **web_profiler.debug_toolbar.intercept_redirects**: ?

Services
--------

* **stopwatch**: A stopwatch that can be used to track time and memory usage.
.. * **profiler**: ?
.. * **profiler.listener**: ?
.. * **profiler.storage**: ?
.. * **web_profiler.toolbar.listener**: ?
.. * **web_profiler.controller.profiler**: ?
.. * **web_profiler.controller.router**: ?
.. * **web_profiler.controller.exception**: ?
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

Profile task with the stopwatch service.

.. code-block:: php

    $stopwatch = $app['stopwatch'];
    $stopwatch->start('query');
    // ...
    $event = $stopwatch->stop('query');

You can add a category argument to color code it.

.. code-block:: php

    $stopwatch = $app['stopwatch'];
    $stopwatch->start('query', 'doctrine');
    // ...
    $event = $stopwatch->stop('query');

.. tip::

    The WebProfilerServiceProvider comes with six categories.
    * default
    * section
    * event_listener
    * event_listener_loading
    * template
    * doctrine
    * propel
    * child_sections
    **Any other category will use the same color as default.**

.. tip::
    
    Add more colors by defining your own templates in **data_collector.templates**.

Sections
--------

Sections are a way to logically split the timeline into groups. 
You can see how Symfony uses sections to nicely visualize the framework lifecycle in the Profiler tool. 
Here is a basic usage example using sections:

.. code-block:: php

    $stopwatch = new Stopwatch();

    $stopwatch->openSection();
    $stopwatch->start('parsing_config_file', 'filesystem_operations');
    $stopwatch->stopSection('routing');

You can reopen a closed section by calling the openSection method and specifying the id of the section to be reopened:

.. code-block:: php

    $stopwatch->openSection('routing');
    $stopwatch->start('building_config_tree');
    $stopwatch->stopSection('routing');

Periods
-------

As you know from the real world, all stopwatches come with two buttons: 
one to start and stop the stopwatch, and another to measure the lap time. 
This is exactly what the lap() method does:

.. code-block:: php

    // Start event named 'foo'
    $stopwatch->start('foo');
    // ... some code goes here
    $stopwatch->lap('foo');
    // ... some code goes here
    $stopwatch->lap('foo');
    // ... some other code goes here
    $event = $stopwatch->stop('foo');

Retrieving Data
---------------

.. code-block:: php

    $event->getPeriods();    // Returns an array of the periods
    $event->getCategory();   // Returns the category the event was started in
    $event->getOrigin();     // Returns the event start time in milliseconds
    $event->ensureStopped(); // Stops all periods not already stopped
    $event->getStartTime();  // Returns the start time of the very first period
    $event->getEndTime();    // Returns the end time of the very last period
    $event->getDuration();   // Returns the event duration, including all periods
    $event->getMemory();     // Returns the max memory usage of all periods