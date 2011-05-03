FormExtension
=================

The *FormExtension* provides a service for building form in
your application with the Symfony2 Form component.

Parameters
----------

* **form.secret** (optional): The secret value used for generating the CSRF token. It uses to secure the CSRF token.
  Defaults to md5(__DIR__).

* **form.tmp_dir** (optional): The temp dir where the secret is store.

* **form.class_path**: Path to where
  Symfony2 Form component is located.

Services
--------

* **form.factory**: An instance of `FormFactory
  <http://api.symfony.com/2.0/Symfony/Component/Form/FormFactory.html>`_,
  that is used for build a form.

* **form.csrf_provider**: An instance of an implementation of the `CsrfProviderInterface
  <http://api.symfony.com/2.0/Symfony/Component/Form/Extension/Csrf/CsrfProvider/CsrfProviderInterface.html>`_,
  defaults to a `DefaultCsrfProvider
  <http://api.symfony.com/2.0/Symfony/Component/Form/Extension/Csrf/CsrfProvider/DefaultCsrfProvider.html>`_.

* **form.storage**: An instance of `TemporaryStorage
  <http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/File/TemporaryStorage.html>`_

Registering
-----------

Make sure you place a copy of `Symfony/symfony
<https://github.com/symfony/symfony>`_ in `vendor/symfony`

::

    use Silex\Extension\SymfonyBridgesExtension;
    use Silex\Extension\TranslationExtension;
    use Silex\Extension\FormExtension;

    $app->register(new SymfonyBridgesExtension(), array(
        'symfony_bridges.class_path' => __DIR__ . '/vendor/symfony/src'
    ));

    $app->register(new TranslationExtension(), array(
        'translation.class_path' => __DIR__ . '/vendor/symfony/src',
        'translator.messages'    => array()
    ));

    $app->register(new FormExtension(), array(
        'form.class_path' => __DIR__ . '/vendor/symfony/src'
    ));

Usage
-----

The FormExtension provides a ``form.factory`` service. Here is a usage
example::

    $app->get('/', function ($id) use ($app) {
        $form = $app['form.factory']->createBuilder('form')
            ->add('name', 'text')
            ->add('email', 'text')
            ->add('message', 'textarea')
        ->getForm();

        return $app['twig']->render('index.twig', array('form' => $form->createView()));
    });

This will render a file named ``views/index.twig``.
Be sure to to copy the `div_layout.html.twig <https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/TwigBundle/Resources/views/Form/div_layout.html.twig>`_ in views path.

For more information, consult the `Symfony Forms documentation
<http://symfony.com/doc/2.0/book/forms.html>`_.
