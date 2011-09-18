SymfonyBridgesExtension
=======================

The *SymfonyBridgesExtension* provides additional integration between
Symfony2 components and libraries.

Parameters
----------

* **symfony_bridges.class_path** (optional): Path to where
  the Symfony2 Bridges are located.

Twig
----

When the ``SymfonyBridgesExtension`` is enabled, the ``TwigExtension`` will
provide you with the following additional capabilities:

* **UrlGeneratorExtension**: If you are using the ``UrlGeneratorExtension``,
  you will get ``path`` and ``url`` helpers for Twig. You can find more
  information in the
  `Symfony2 Routing documentation <http://symfony.com/doc/current/book/routing.html#generating-urls-from-a-template>`_.

* **TranslationExtension**: If you are using the ``TranslationExtension``,
  you will get ``trans`` and ``transchoice`` helpers for translation in
  Twig templates. You can find more information in the
  `Symfony2 Translation documentation <http://symfony.com/doc/current/book/translation.html#twig-templates>`_.

* **FormExtension**: If you are using the ``FormExtension``,
  you will get a set of helpers for working with forms in templates.
  You can find more information in the
  `Symfony2 Forms reference <http://symfony.com/doc/current/reference/forms/twig_reference.html>`_.

Registering
-----------

Make sure you place a copy of the Symfony2 Bridges in
``vendor/symfony/src``. You can simply clone the whole Symfony2 into vendor::

    $app->register(new Silex\Extension\SymfonyBridgesExtension(), array(
        'symfony_bridges.class_path'  => __DIR__.'/vendor/symfony/src',
    ));
