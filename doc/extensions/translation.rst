TranslationExtension
=====================

The *TranslationExtension* provides a service for translating your application
into different languages.

Parameters
----------

* **translator.messages**: A mapping of locales to message arrays. This parameter
  contains the translation data in all languages.

* **locale** (optional): The locale for the translator. You will most likely want
  to set this based on some request parameter. Defaults to ``en``.

* **locale_fallback** (optional): Fallback locale for the translator. It will
  be used when the current locale has no messages set.

* **translation.class_path** (optional): Path to where
  the Symfony2 Translation component is located.

Services
--------

* **translator**: An instance of `Translator
  <http://api.symfony.com/2.0/Symfony/Component/Translation/Translator.html>`_,
  that is used for translation.

* **translator.loader**: An instance of an implementation of the translation
  `LoaderInterface <http://api.symfony.com/2.0/Symfony/Component/Translation/Loader/LoaderInterface.html>`_,
  defaults to an `ArrayLoader
  <http://api.symfony.com/2.0/Symfony/Component/Translation/Loader/ArrayLoader.html>`_.

* **translator.message_selector**: An instance of `MessageSelector
  <http://api.symfony.com/2.0/Symfony/Component/Translation/MessageSelector.html>`_.

Registering
-----------

Make sure you place a copy of the Symfony2 Translation component in
``vendor/symfony/src``. You can simply clone the whole Symfony2 into vendor.

::

    $app->register(new Silex\Extension\TranslationExtension(), array(
        'locale_fallback'           => 'en',
        'translation.class_path'    => __DIR__.'/vendor/symfony/src',
    ));

Usage
-----

The Translation extension provides a ``translator`` service and makes use of
the ``translator.messages`` parameter.

::

    $app['translator.messages'] = array(
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

Having your translation in PHP files can be inconvenient. This recipe will
show you how to load translations from external YAML files.

First you will need the ``Config`` and ``Yaml`` components from Symfony2. Also
make sure you register them with the autoloader. You can just clone the entire
Symfony2 repository into ``vendor/symfony``.

::

    $app['autoloader']->registerNamespace('Symfony', __DIR__.'/vendor/symfony/src');

Next, you have to create the language mappings in YAML files. A naming you can
use is ``locales/en.yml``. Just do the mapping in this file as follows:

.. code-block:: yaml

    hello: Hello %name%
    goodbye: Goodbye %name%

Repeat this for all of your languages. Then set up the ``translator.messages`` to map
languages to files::

    $app['translator.messages'] = array(
        'en' => __DIR__.'/locales/en.yml',
        'de' => __DIR__.'/locales/de.yml',
        'fr' => __DIR__.'/locales/fr.yml',
    );

Finally override the ``translator.loader`` to use a ``YamlFileLoader`` instead of the
default ``ArrayLoader``::

    $app['translator.loader'] = new Symfony\Component\Translation\Loader\YamlFileLoader();

And that's all you need to load translations from YAML files.
