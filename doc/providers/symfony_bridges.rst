SymfonyBridgesServiceProvider
=============================

The *SymfonyBridgesServiceProvider* provides additional integration between
Symfony2 components and libraries.

Parameters
----------

* **symfony_bridges.class_path** (optional): Path to where
  the Symfony2 Bridges are located.

Twig
----

When the ``SymfonyBridgesServiceProvider`` is enabled, the ``TwigServiceProvider`` will
provide you with the following additional capabilities:

* **UrlGeneratorServiceProvider**: If you are using the ``UrlGeneratorServiceProvider``,
  you will get ``path`` and ``url`` helpers for Twig. You can find more
  information in the
  `Symfony2 Routing documentation <http://symfony.com/doc/current/book/routing.html#generating-urls-from-a-template>`_.

* **TranslationServiceProvider**: If you are using the ``TranslationServiceProvider``,
  you will get ``trans`` and ``transchoice`` helpers for translation in
  Twig templates. You can find more information in the
  `Symfony2 Translation documentation <http://symfony.com/doc/current/book/translation.html#twig-templates>`_.

* **FormServiceProvider**: If you are using the ``FormServiceProvider``,
  you will get a set of helpers for working with forms in templates.
  You can find more information in the
  `Symfony2 Forms reference <http://symfony.com/doc/current/reference/forms/twig_reference.html>`_.

Registering
-----------

Make sure you place a copy of the Symfony2 Bridges in either
``vendor/symfony/src`` by cloning `Symfony2 <https://github.com/symfony/symfony>`_ or
``vendor/symfony/src/Symfony/Bridge/Twig`` by cloning `TwigBridge <https://github.com/symfony/TwigBridge>`_
(the latter having a smaller footprint).

Then, register the provider via::

    $app->register(new Silex\Provider\SymfonyBridgesServiceProvider(), array(
        'symfony_bridges.class_path'  => __DIR__.'/vendor/symfony/src',
    ));
