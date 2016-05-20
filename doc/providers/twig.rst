Twig
====

The *TwigServiceProvider* provides integration with the `Twig
<http://twig.sensiolabs.org/>`_ template engine.

Parameters
----------

* **twig.path** (optional): Path to the directory containing twig template
  files (it can also be an array of paths).

* **twig.templates** (optional): An associative array of template names to
  template contents. Use this if you want to define your templates inline.

* **twig.options** (optional): An associative array of twig
  options. Check out the `twig documentation <http://twig.sensiolabs.org/doc/api.html#environment-options>`_
  for more information.

* **twig.form.templates** (optional): An array of templates used to render
  forms (only available when the ``FormServiceProvider`` is enabled). The
  default theme is ``form_div_layout.html.twig``, but you can use the other
  built-in themes: ``form_table_layout.html.twig``,
  ``bootstrap_3_layout.html.twig``, and
  ``bootstrap_3_horizontal_layout.html.twig``.

Services
--------

* **twig**: The ``Twig_Environment`` instance. The main way of
  interacting with Twig.

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

    Add Twig as a dependency:

    .. code-block:: bash

        composer require twig/twig

Usage
-----

The Twig provider provides a ``twig`` service that can render templates::

    $app->get('/hello/{name}', function ($name) use ($app) {
        return $app['twig']->render('hello.twig', array(
            'name' => $name,
        ));
    });

Symfony Components Integration
------------------------------

Symfony provides a Twig bridge that provides additional integration between
some Symfony components and Twig. Add it as a dependency:

.. code-block:: bash

    composer require symfony/twig-bridge

When present, the ``TwigServiceProvider`` will provide you with the following
additional capabilities.

* Access to the ``path()`` and ``url()`` functions. You can find more
  information in the `Symfony Routing documentation
  <http://symfony.com/doc/current/book/routing.html#generating-urls-from-a-template>`_:

  .. code-block:: jinja
  
      {{ path('homepage') }}
      {{ url('homepage') }} {# generates the absolute url http://example.org/ #}
      {{ path('hello', {name: 'Fabien'}) }}
      {{ url('hello', {name: 'Fabien'}) }} {# generates the absolute url http://example.org/hello/Fabien #}

* Access to the ``absolute_url()`` and ``relative_path()`` Twig functions.

Translations Support
~~~~~~~~~~~~~~~~~~~~

If you are using the ``TranslationServiceProvider``, you will get the
``trans()`` and ``transchoice()`` functions for translation in Twig templates.
You can find more information in the `Symfony Translation documentation
<http://symfony.com/doc/current/book/translation.html#twig-templates>`_.

Form Support
~~~~~~~~~~~~

If you are using the ``FormServiceProvider``, you will get a set of helpers for
working with forms in templates. You can find more information in the `Symfony
Forms reference
<http://symfony.com/doc/current/reference/forms/twig_reference.html>`_.

Security Support
~~~~~~~~~~~~~~~~

If you are using the ``SecurityServiceProvider``, you will have access to the
``is_granted()`` function in templates. You can find more information in the
`Symfony Security documentation
<http://symfony.com/doc/current/book/security.html#access-control-in-templates>`_.

Global Variable
~~~~~~~~~~~~~~~

When the Twig bridge is available, the ``global`` variable refers to an
instance of `AppVariable <http://api.symfony.com/master/Symfony/Bridge/Twig/AppVariable.html>`_.
It gives access to the following methods:

.. code-block:: jinja

    {# The current Request #}
    {{ global.request }}

    {# The current User (when security is enabled) #}
    {{ global.user }}

    {# The current Session #}
    {{ global.session }}

    {# The debug flag #}
    {{ global.debug }}

Rendering a Controller
~~~~~~~~~~~~~~~~~~~~~~

A ``render`` function is also registered to help you render another controller
from a template (available when the :doc:`HttpFragment Service Provider
</providers/http_fragment>` is registered):

.. code-block:: jinja

    {{ render(url('sidebar')) }}

    {# or you can reference a controller directly without defining a route for it #}
    {{ render(controller(controller)) }}

.. note::

    You must prepend the ``app.request.baseUrl`` to render calls to ensure
    that the render works when deployed into a sub-directory of the docroot.

.. note::

    Read the Twig `reference`_ for Symfony document to learn more about the
    various Twig functions.

Traits
------

``Silex\Application\TwigTrait`` adds the following shortcuts:

* **render**: Renders a view with the given parameters and returns a Response
  object.

.. code-block:: php

    return $app->render('index.html', ['name' => 'Fabien']);

    $response = new Response();
    $response->setTtl(10);

    return $app->render('index.html', ['name' => 'Fabien'], $response);

.. code-block:: php

    // stream a view
    use Symfony\Component\HttpFoundation\StreamedResponse;

    return $app->render('index.html', ['name' => 'Fabien'], new StreamedResponse());

Customization
-------------

You can configure the Twig environment before using it by extending the
``twig`` service::

    $app->extend('twig', function($twig, $app) {
        $twig->addGlobal('pi', 3.14);
        $twig->addFilter('levenshtein', new \Twig_Filter_Function('levenshtein'));

        return $twig;
    });

For more information, check out the `official Twig documentation
<http://twig.sensiolabs.org>`_.

.. _reference: https://symfony.com/doc/current/reference/twig_reference.html#controller
