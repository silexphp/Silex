TwigServiceProvider
===================

The *TwigServiceProvider* provides integration with the `Twig
<http://twig.sensiolabs.org/>`_ template engine.

Parameters
----------

* **twig.path** (optional): Path to the directory containing twig template
  files (it can also be an array of paths).

* **twig.templates** (optional): An associative array of template names to
  template contents. Use this if you want to define your templates inline.

* **twig.options** (optional): An associative array of twig
  options. Check out the twig documentation for more information.

* **twig.form.templates** (optional): An array of templates used to render
  forms (only available when the ``FormServiceProvider`` is enabled).

Services
--------

* **twig**: The ``Twig_Environment`` instance. The main way of
  interacting with Twig.

* **twig.configure**: :doc:`Protected closure </services#protected-closures>`
  that takes the Twig environment as an argument. You can use it to add more
  custom globals.

* **twig.loader**: The loader for Twig templates which uses the ``twig.path``
  and the ``twig.templates`` options. You can also replace the loader
  completely.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/views',
    ));

.. note::

    Twig does not come with the ``silex`` archives, so you need to add it as a
    dependency to your ``composer.json`` file:

    .. code-block:: json

        "require": {
            "twig/twig": ">=1.8,<2.0-dev"
        }

Symfony2 Components Integration
-------------------------------

Symfony provides a Twig bridge that provides additional integration between
some Symfony2 components and Twig. Add it as a dependency to your
``composer.json`` file:

.. code-block:: json

    "require": {
        "symfony/twig-bridge": "2.1.*",
    }

When present, the ``TwigServiceProvider`` will provide you with the following
additional capabilities:

* **UrlGeneratorServiceProvider**: If you are using the
  ``UrlGeneratorServiceProvider``, you will have access to the ``path()`` and
  ``url()`` functions. You can find more information in the `Symfony2 Routing
  documentation
  <http://symfony.com/doc/current/book/routing.html#generating-urls-from-a-template>`_.

* **TranslationServiceProvider**: If you are using the
  ``TranslationServiceProvider``, you will get the ``trans()`` and
  ``transchoice()`` functions for translation in Twig templates. You can find
  more information in the `Symfony2 Translation documentation
  <http://symfony.com/doc/current/book/translation.html#twig-templates>`_.

* **FormServiceProvider**: If you are using the ``FormServiceProvider``, you
  will get a set of helpers for working with forms in templates. You can find
  more information in the `Symfony2 Forms reference
  <http://symfony.com/doc/current/reference/forms/twig_reference.html>`_.

Usage
-----

The Twig provider provides a ``twig`` service::

    $app->get('/hello/{name}', function ($name) use ($app) {
        return $app['twig']->render('hello.twig', array(
            'name' => $name,
        ));
    });

This will render a file named ``views/hello.twig``.

In any Twig template, the ``app`` variable refers to the Application object.
So you can access any services from within your view. For example to access
``$app['request']->getHost()``, just put this in your template:

.. code-block:: jinja

    {{ app.request.host }}

A ``render`` function is also registered to help you render another controller
from a template:

.. code-block:: jinja

    {{ render('/sidebar') }}

    {# or if you are also using UrlGeneratorServiceProvider with the SymfonyBridgesServiceProvider #}
    {{ render(path('sidebar')) }}

For more information, check out the `Twig documentation
<http://twig.sensiolabs.org>`_.
