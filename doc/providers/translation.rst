TranslationServiceProvider
==========================

The *TranslationServiceProvider* provides a service for translating your
application into different languages.

Parameters
----------

* **translator.domains**: A mapping of domains/locales/messages. This
  parameter contains the translation data for all languages and domains.

* **locale** (optional): The locale for the translator. You will most likely
  want to set this based on some request parameter. Defaults to ``en``.

* **locale_fallback** (optional): Fallback locale for the translator. It will
  be used when the current locale has no messages set.

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

    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'locale_fallback' => 'en',
    ));

.. note::

    The Symfony Translation component does not come with the ``silex``
    archives, so you need to add it as a dependency to your ``composer.json``
    file:

    .. code-block:: json

        "require": {
            "symfony/translation": "2.1.*"
        }

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

    $app->before(function () use ($app) {
        if ($locale = $app['request']->get('locale')) {
            $app['locale'] = $locale;
        }
    });

    $app->get('/{locale}/{message}/{name}', function ($message, $name) use ($app) {
        return $app['translator']->trans($message, array('%name%' => $name));
    });

The above example will result in following routes:

* ``/en/hello/igor`` will return ``Hello igor``.

* ``/de/hello/igor`` will return ``Hallo igor``.

* ``/fr/hello/igor`` will return ``Bonjour igor``.

* ``/it/hello/igor`` will return ``Hello igor`` (because of the fallback).

Recipes
-------

YAML-based language files
~~~~~~~~~~~~~~~~~~~~~~~~~

Having your translations in PHP files can be inconvenient. This recipe will
show you how to load translations from external YAML files.

First, add the Symfony2 ``Config`` and ``Yaml`` components in your composer
file:

.. code-block:: json

    "require": {
        "symfony/config": "2.1.*",
        "symfony/yaml": "2.1.*",
    }

Next, you have to create the language mappings in YAML files. A naming you can
use is ``locales/en.yml``. Just do the mapping in this file as follows:

.. code-block:: yaml

    hello: Hello %name%
    goodbye: Goodbye %name%

Repeat this for all of your languages. Then set up the ``translator.domains``
to map languages to files::

    $app['translator.domains'] = array(
        'messages' => array(
            'en' => __DIR__.'/locales/en.yml',
            'de' => __DIR__.'/locales/de.yml',
            'fr' => __DIR__.'/locales/fr.yml',
        ),
    );

Finally override the ``translator.loader`` to use a ``YamlFileLoader`` instead
of the default ``ArrayLoader``::

    use Symfony\Component\Translation\Loader\YamlFileLoader;

    $app['translator.loader'] = $app->share(function () {
        return new YamlFileLoader();
    });

That's all you need to load translations from YAML files.

XLIFF-based language files
~~~~~~~~~~~~~~~~~~~~~~~~~~

Just as you would do with YAML translation files, you first need to add the
Symfony2 ``Config`` component as a dependency (see above for details).

Then, similarly, create XLIFF files in your locales directory and setup the
``translator.domains`` to map to them.

Finally override the ``translator.loader`` to use a ``XliffFileLoader``::

    use Symfony\Component\Translation\Loader\XliffFileLoader;

    $app['translator.loader'] = $app->share(function () {
        return new XliffFileLoader();
    });

That's it.

Accessing translations in Twig templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Once loaded, the translation service provider is available from within Twig
templates:

.. code-block:: jinja

    {{ app.translator.trans('translation_key') }}

Moreover, when using the Twig bridge provided by Symfony (see
:doc:`TwigServiceProvider </providers/twig>`), you will be allowed to translate
strings in the Twig way:

.. code-block:: jinja

    {{ 'translation_key'|trans }}
    {{ 'translation_key'|transchoice }}
    {% trans %}translation_key{% endtrans %}
