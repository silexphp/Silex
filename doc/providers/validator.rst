ValidatorServiceProvider
========================

The *ValidatorServiceProvider* provides a service for validating data. It is
most useful when used with the *FormServiceProvider*, but can also be used
standalone.

Parameters
----------

none

Services
--------

* **validator**: An instance of `Validator
  <http://api.symfony.com/master/Symfony/Component/Validator/Validator.html>`_.

* **validator.mapping.class_metadata_factory**: Factory for metadata loaders,
  which can read validation constraint information from classes. Defaults to
  StaticMethodLoader--ClassMetadataFactory.

  This means you can define a static ``loadValidatorMetadata`` method on your
  data class, which takes a ClassMetadata argument. Then you can set
  constraints on this ClassMetadata instance.

* **validator.validator_factory**: Factory for ConstraintValidators. Defaults
  to a standard ``ConstraintValidatorFactory``. Mostly used internally by the
  Validator.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\ValidatorServiceProvider());

.. note::

    The Symfony Validator component does not come with the ``silex`` archives,
    so you need to add it as a dependency to your ``composer.json`` file:

    .. code-block:: json

        "require": {
            "symfony/validator": "2.1.*"
        }

Usage
-----

The Validator provider provides a ``validator`` service.

Validating values
~~~~~~~~~~~~~~~~~

You can validate values directly using the ``validateValue`` validator
method::

    use Symfony\Component\Validator\Constraints;

    $app->get('/validate-url', function () use ($app) {
        $violations = $app['validator']->validateValue($app['request']->get('url'), new Constraints\Url());
        return $violations;
    });

This is relatively limited.

Validating object properties
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to add validations to a class, you can implement a static
``loadValidatorMetadata`` method as described under *Services*. This allows
you to define constraints for your object properties. It also works with
getters::

    use Symfony\Component\Validator\Mapping\ClassMetadata;
    use Symfony\Component\Validator\Constraints;

    class Post
    {
        public $title;
        public $body;

        static public function loadValidatorMetadata(ClassMetadata $metadata)
        {
            $metadata->addPropertyConstraint('title', new Constraints\NotNull());
            $metadata->addPropertyConstraint('title', new Constraints\NotBlank());
            $metadata->addPropertyConstraint('body', new Constraints\MinLength(array('limit' => 10)));
        }
    }

    $app->post('/posts/new', function () use ($app) {
        $post = new Post();
        $post->title = $app['request']->get('title');
        $post->body = $app['request']->get('body');

        $violations = $app['validator']->validate($post);
        return $violations;
    });

You will have to handle the display of these violations yourself. You can
however use the *FormServiceProvider* which can make use of the *ValidatorServiceProvider*.

Translation
~~~~~~~~~~~

To be able to translate the error messages, you can use the translator
provider and register the messages under the ``validators`` domain::

    $app['translator.domains'] = array(
        'validators' => array(
            'fr' => array(
                'This value should be a valid number.' => 'Cette valeur doit être un nombre.',
            ),
        ),
    );

For more information, consult the `Symfony2 Validation documentation
<http://symfony.com/doc/2.0/book/validation.html>`_.
