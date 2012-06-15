FormServiceProvider
===================

The *FormServiceProvider* provides a service for building forms in
your application with the Symfony2 Form component.

Parameters
----------

* **form.secret**: This secret value is used for generating and validating the
  CSRF token for a specific page. It is very important for you to set this
  value to a static randomly generated value, to prevent hijacking of your
  forms. Defaults to ``md5(__DIR__)``.

Services
--------

* **form.factory**: An instance of `FormFactory
  <http://api.symfony.com/master/Symfony/Component/Form/FormFactory.html>`_,
  that is used for build a form.

* **form.csrf_provider**: An instance of an implementation of the
  `CsrfProviderInterface
  <http://api.symfony.com/master/Symfony/Component/Form/Extension/Csrf/CsrfProvider/CsrfProviderInterface.html>`_,
  defaults to a `DefaultCsrfProvider
  <http://api.symfony.com/master/Symfony/Component/Form/Extension/Csrf/CsrfProvider/DefaultCsrfProvider.html>`_.

Registering
-----------

.. code-block:: php

    use Silex\Provider\FormServiceProvider;

    $app->register(new FormServiceProvider());

.. note::

    The Symfony Form Component comes with the "fat" Silex archive but not with
    the regular one. If you are using Composer, add it as a dependency to your
    ``composer.json`` file:

    .. code-block:: json

        "require": {
            "symfony/form": "2.1.*"
        }

Usage
-----

The FormServiceProvider provides a ``form.factory`` service. Here is a usage
example::

    $app->get('/hello/{name}', function ($name) use ($app) {
        return "Hello $name!";
    })->bind('hello');

    $app->match('/', function (Request $request) use ($app) {
        $form = $app['form.factory']->createBuilder('form')
            ->add('name', 'text')
            ->getForm();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                // do something with the data

                return $app->redirect('/hello/{name}');
            }
        }

        return $app['twig']->render('index.twig', array('form' => $form->createView()));
    });

Put this in your template file named ``views/index.twig``:

.. code-block:: jinja

    <form action="#" method="post">
        {{ form_widget(form) }}

        <input type="submit" name="submit" />
    </form>

If you are using the validator provider, you can also add validation to your
form by adding constraints on the fields::

    use Symfony\Component\Validator\Constraints as Assert;

    $app->register(new Silex\Provider\ValidatorServiceProvider());
    $app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'translator.messages' => array(),
    ));

    $form = $app['form.factory']->createBuilder('form')
        ->add('name', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(5))
        ))
        ->getForm();

For more information, consult the `Symfony2 Forms documentation
<http://symfony.com/doc/2.1/book/forms.html>`_.
