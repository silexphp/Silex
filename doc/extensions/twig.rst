TwigExtension
=============

The *TwigExtension* provides integration with the `Twig
<http://www.twig-project.org/>`_ template engine.

Parameters
----------

* **twig.path**: Path to the directory containing twig template
  files.

* **twig.templates** (optional): If this option is provided
  you don't have to provide a ``twig.path``. It is an
  associative array of template names to template contents.
  Use this if you want to define your templates inline.

* **twig.options** (optional): An associative array of twig
  options. Check out the twig documentation for more information.

* **twig.class_path** (optional): Path to where the Twig
  library is located.

Services
--------

* **twig**: The ``Twig_Environment`` instance. The main way of
  interacting with Twig.

* **twig.configure**: Protected closure that takes the Twig
  environment as an argument. You can use it to add more
  custom globals.

* **twig.loader**: The loader for Twig templates which uses
  the ``twig.path`` and the ``twig.templates`` options. You
  can also replace the loader completely.

Registering
-----------

Make sure you place a copy of *Twig* in the ``vendor/twig``
directory.

::

    $app->register(new Silex\Extension\TwigExtension(), array(
        'twig.path'       => __DIR__.'/views',
        'twig.class_path' => __DIR__.'/vendor/twig/lib',
    ));

.. note::

    Twig is not compiled into the ``silex.phar`` file. You have to
    add your own copy of Twig to your application.

Usage
-----

The Twig extension provides a ``twig`` service.

::

    $app->get('/hello/{name}', function ($name) use ($app) {
        return $app['twig']->render('hello.twig', array(
            'name' => $name,
        ));
    });

This will render a file named ``views/hello.twig``.

It also registers the application as a global named
``app``. So you can access any services from within your
view. For example to access ``$app['request']->getHost()``,
just put this in your template:

.. code-block:: jinja

    {{ app.request.host }}
