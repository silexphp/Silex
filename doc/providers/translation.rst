Translation
===========

The *TranslationServiceProvider* provides a service for translating your
application into different languages.

Parameters
----------

* **translator.domains** (optional): A mapping of domains/locales/messages.
  This parameter contains the translation data for all languages and domains.

* **locale** (optional): The locale for the translator. You will most likely
  want to set this based on some request parameter. Defaults to ``en``.

* **locale_fallbacks** (optional): Fallback locales for the translator. It will
  be used when the current locale has no messages set. Defaults to ``en``.

Services
--------

* **translator**: An instance of `Translator
  <http://api.symfony.com/master/Symfony/Component/Translation/Translator.html>`_,
  that is used for translation.

* **translator.loader**: An instance of an implementation of the translation
  `LoaderInterface
  <http://api.symfony.com/master/Symfony/Component/Translation/Loader/LoaderInterface.html>`_,
  defaults to an `ArrayLoader
  <http://api.symfony.com/master/Symfony/Component/Translation/Loader/ArrayLoader.html>`_.

* **translator.message_selector**: An instance of `MessageSelector
  <http://api.symfony.com/master/Symfony/Component/Translation/MessageSelector.html>`_.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\LocaleServiceProvider());
    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'locale_fallbacks' => array('en'),
    ));

.. note::

    Add the Symfony Translation Component as a dependency:

    .. code-block:: bash

        composer require symfony/translation

Usage
-----

The Translation provider provides a ``translator`` service and makes use of
the ``translator.domains`` parameter::

    $app['translator.domains'] = array(
        'messages' => array(
            'en' => array(
                'hello'     => 'Hello %name%',
                'goodbye'   => 'Goodbye %name%',
            ),
            'de' => array(
                'hello'     => 'Hallo %name%',
                'goodbye'   => 'Tschüss %name%',
            ),
            'fr' => array(
                'hello'     => 'Bonjour %name%',
                'goodbye'   => 'Au revoir %name%',
            ),
        ),
        'validators' => array(
            'fr' => array(
                'This value should be a valid number.' => 'Cette valeur doit être un nombre.',
            ),
        ),
    );

    $app->get('/{_locale}/{message}/{name}', function ($message, $name) use ($app) {
        return $app['translator']->trans($message, array('%name%' => $name));
    });

The above example will result in following routes:

* ``/en/hello/igor`` will return ``Hello igor``.

* ``/de/hello/igor`` will return ``Hallo igor``.

* ``/fr/hello/igor`` will return ``Bonjour igor``.

* ``/it/hello/igor`` will return ``Hello igor`` (because of the fallback).

Using Resources
---------------

When translations are stored in a file, you can load them as follows::

    $app = new Application();
    
    $app->register(new TranslationServiceProvider());
    $app->extend('translator.resources', function ($resources, $app) {
        $resources = array_merge($resources, array(
            array('array', array('This value should be a valid number.' => 'Cette valeur doit être un nombre.'), 'fr', 'validators'),
        ));

        return $resources;
    });

Traits
------

``Silex\Application\TranslationTrait`` adds the following shortcuts:

* **trans**: Translates the given message.

* **transChoice**: Translates the given choice message by choosing a
  translation according to a number.

.. code-block:: php

    $app->trans('Hello World');

    $app->transChoice('Hello World');

Recipes
-------

YAML-based language files
~~~~~~~~~~~~~~~~~~~~~~~~~

Having your translations in PHP files can be inconvenient. This recipe will
show you how to load translations from external YAML files.

First, add the Symfony ``Config`` and ``Yaml`` components as dependencies:

.. code-block:: bash

    composer require symfony/config symfony/yaml

Next, you have to create the language mappings in YAML files. A naming you can
use is ``locales/en.yml``. Just do the mapping in this file as follows:

.. code-block:: yaml

    hello: Hello %name%
    goodbye: Goodbye %name%

Then, register the ``YamlFileLoader`` on the ``translator`` and add all your
translation files::

    use Symfony\Component\Translation\Loader\YamlFileLoader;

    $app->extend('translator', function($translator, $app) {
        $translator->addLoader('yaml', new YamlFileLoader());

        $translator->addResource('yaml', __DIR__.'/locales/en.yml', 'en');
        $translator->addResource('yaml', __DIR__.'/locales/de.yml', 'de');
        $translator->addResource('yaml', __DIR__.'/locales/fr.yml', 'fr');

        return $translator;
    });

XLIFF-based language files
~~~~~~~~~~~~~~~~~~~~~~~~~~

Just as you would do with YAML translation files, you first need to add the
Symfony ``Config`` component as a dependency (see above for details).

Then, similarly, create XLIFF files in your locales directory and add them to
the translator::

    $translator->addResource('xliff', __DIR__.'/locales/en.xlf', 'en');
    $translator->addResource('xliff', __DIR__.'/locales/de.xlf', 'de');
    $translator->addResource('xliff', __DIR__.'/locales/fr.xlf', 'fr');

.. note::

    The XLIFF loader is already pre-configured by the extension.

Accessing translations in Twig templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Once loaded, the translation service provider is available from within Twig
templates when using the Twig bridge provided by Symfony (see
:doc:`TwigServiceProvider </providers/twig>`):

.. code-block:: jinja

    {{ 'translation_key'|trans }}
    {{ 'translation_key'|transchoice }}
    {% trans %}translation_key{% endtrans %}
