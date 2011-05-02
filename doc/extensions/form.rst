FormExtension
=================

The *Form Extension* provides integration with the `Symfony/Form
<https://github.com/symfony/Form>`_ component.

Parameters
----------

* **form.class_path** : Path to where
  Symfony Form is located.

Services
--------

* **form.factory** : The ``Symfony\Component\Form\FormFactory`` instance.

* **form.csrf_provider** (optional): CSRF Provider object.

* **form.storage** (optional): TemporaryStorage object.

* **form.class_path** : Path to where
  Symfony Form is located

Registering
-----------

Make sure you place a copy of :
 * `Symfony/TwigBridge <https://github.com/symfony/TwigBridge>`_
 in ``vendor/symfony/Symfony/Bridge/Twig``
 * `Symfony/Form <https://github.com/symfony/Form>`_
 in ``vendor/symfony/Symfony/Component``
 * `Symfony/Translation <https://github.com/symfony/Translation>`_
 in ``vendor/symfony/Symfony/Component``

::

    use Silex\Extension\SymfonyBridgesExtension;
    use Silex\Extension\TranslationExtension;
    use Silex\Extension\FormExtension;

    $app->register(new SymfonyBridgesExtension(), array(
        'symfony_bridges.class_path' => __DIR__ . '/vendor/symfony'
    ));

    $app->register(new TranslationExtension(), array(
        'translation.class_path' => __DIR__ . '/vendor/symfony',
        'translator.messages' => array()
    ));

    $app->register(new FormExtension(), array(
        'form.class_path' => __DIR__ . '/vendor/symfony'
    ));

    .. note::

        TwigBridge, Form and Translation are not compiled into the ``silex.phar`` file. You have to
        add your own copy of them to your application.

Usage
-----

The Form extension provides a ``form.factory`` service. Here is a usage
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
