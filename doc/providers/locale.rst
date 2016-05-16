Locale
======

The *LocaleServiceProvider* manages the locale of an application.

Parameters
----------

* **locale**: The locale of the user. When set before any request handling, it
  defines the default locale (``en`` by default). When a request is being
  handled, it is automatically set according to the ``_locale`` request
  attribute of the current route.

Services
--------

* n/a

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\LocaleServiceProvider());
