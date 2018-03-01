Form
====

The *FormServiceProvider* provides a service for building forms in
your application with the Symfony Form component.

Parameters
----------

* none

Services
--------

* **form.factory**: An instance of `FormFactory
  <http://api.symfony.com/master/Symfony/Component/Form/FormFactory.html>`_,
  that is used to build a form.

Registering
-----------

.. code-block:: php

    use Silex\Provider\FormServiceProvider;

    $app->register(new FormServiceProvider());

.. note::

    If you don't want to create your own form layout, it's fine: a default one
    will be used. But you will have to register the :doc:`translation provider
    <translation>` as the default form layout requires it::

        $app->register(new Silex\Provider\TranslationServiceProvider(), array(
            'translator.domains' => array(),
        ));

    If you want to use validation with forms, do not forget to register the
    :doc:`Validator provider <validator>`.

.. note::

    Add the Symfony Form Component as a dependency:

    .. code-block:: bash

        composer require symfony/form

    If you are going to use the validation extension with forms, you must also
    add a dependency to the ``symfony/validator`` and ``symfony/config``
    components:

    .. code-block:: bash

        composer require symfony/validator symfony/config

    If you want to use forms in your Twig templates, you can also install the
    Symfony Twig Bridge. Make sure to install, if you didn't do that already,
    the Translation component in order for the bridge to work:

    .. code-block:: bash

        composer require symfony/twig-bridge

Usage
-----

The FormServiceProvider provides a ``form.factory`` service. Here is a usage
example::

    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
    use Symfony\Component\Form\Extension\Core\Type\FormType;
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;

    $app->match('/form', function (Request $request) use ($app) {
        // some default data for when the form is displayed the first time
        $data = array(
            'name' => 'Your name',
            'email' => 'Your email',
        );

        $form = $app['form.factory']->createBuilder(FormType::class, $data)
            ->add('name')
            ->add('email')
            ->add('billing_plan', ChoiceType::class, array(
                'choices' => array('free' => 1, 'small business' => 2, 'corporate' => 3),
                'expanded' => true,
            ))
            ->add('submit', SubmitType::class, [
                'label' => 'Save',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            // do something with the data

            // redirect somewhere
            return $app->redirect('...');
        }

        // display the form
        return $app['twig']->render('index.twig', array('form' => $form->createView()));
    });

And here is the ``index.twig`` form template (requires ``symfony/twig-bridge``):

.. code-block:: jinja

    <form action="#" method="post">
        {{ form_widget(form) }}

        <input type="submit" name="submit" />
    </form>

If you are using the validator provider, you can also add validation to your
form by adding constraints on the fields::

    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
    use Symfony\Component\Form\Extension\Core\Type\FormType;
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Validator\Constraints as Assert;

    $app->register(new Silex\Provider\ValidatorServiceProvider());
    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'translator.domains' => array(),
    ));

    $form = $app['form.factory']->createBuilder(FormType::class)
        ->add('name', TextType::class, array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 5)))
        ))
        ->add('email', TextType::class, array(
            'constraints' => new Assert\Email()
        ))
        ->add('billing_plan', ChoiceType::class, array(
            'choices' => array('free' => 1, 'small business' => 2, 'corporate' => 3),
            'expanded' => true,
            'constraints' => new Assert\Choice(array(1, 2, 3)),
        ))
        ->add('submit', SubmitType::class, [
            'label' => 'Save',
        ])
        ->getForm();

You can register form types by extending ``form.types``::

    $app['your.type.service'] = function ($app) {
        return new YourServiceFormType();
    };
    $app->extend('form.types', function ($types) use ($app) {
        $types[] = new YourFormType();
        $types[] = 'your.type.service';

        return $types;
    });

You can register form extensions by extending ``form.extensions``::

    $app->extend('form.extensions', function ($extensions) use ($app) {
        $extensions[] = new YourTopFormExtension();

        return $extensions;
    });


You can register form type extensions by extending ``form.type.extensions``::

    $app['your.type.extension.service'] = function ($app) {
        return new YourServiceFormTypeExtension();
    };
    $app->extend('form.type.extensions', function ($extensions) use ($app) {
        $extensions[] = new YourFormTypeExtension();
        $extensions[] = 'your.type.extension.service';

        return $extensions;
    });

You can register form type guessers by extending ``form.type.guessers``::

    $app['your.type.guesser.service'] = function ($app) {
        return new YourServiceFormTypeGuesser();
    };
    $app->extend('form.type.guessers', function ($guessers) use ($app) {
        $guessers[] = new YourFormTypeGuesser();
        $guessers[] = 'your.type.guesser.service';

        return $guessers;
    });

.. warning::

    CSRF protection is only available and automatically enabled when the
    :doc:`CSRF Service Provider </providers/csrf>` is registered.

Traits
------

``Silex\Application\FormTrait`` adds the following shortcuts:

* **form**: Creates a FormBuilderInterface instance.

* **namedForm**: Creates a FormBuilderInterface instance (named).

.. code-block:: php

    $app->form($data);

    $app->namedForm($name, $data, $options, $type);

For more information, consult the `Symfony Forms documentation
<http://symfony.com/doc/current/forms.html>`_.
