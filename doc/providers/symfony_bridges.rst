SymfonyBridgesServiceProvider
=============================

The *SymfonyBridgesServiceProvider* provides additional integration between
Symfony2 components and libraries.

Parameters
----------

none

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

.. code-block:: php

    $app->register(new Silex\Provider\SymfonyBridgesServiceProvider());

.. note::

    The Symfony bridges do not come with the ``silex.zip`, so you need to add
    them as a dependency to your ``composer.json`` file:

    .. code-block:: json

        "require": {
            "symfony/twig-bridge": "2.1.*",
        }
