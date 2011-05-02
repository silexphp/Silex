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

::

    use Silex\Extension\TranslationExtension;

    $app->register(new TranslationExtension(), array(
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
