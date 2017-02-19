Using YAML to configure Validation
==================================

Simplicity is at the heart of Silex so there is no out of the box solution to
use YAML files for validation. But this doesn't mean that this is not
possible. Let's see how to do it.

First, you need to install the YAML Component:

.. code-block:: bash

    composer require symfony/yaml

Next, you need to tell the Validation Service that you are not using
``StaticMethodLoader`` to load your class metadata but a YAML file::

    $app->register(new ValidatorServiceProvider());

    $app['validator.mapping.class_metadata_factory'] = new Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory(
        new Symfony\Component\Validator\Mapping\Loader\YamlFileLoader(__DIR__.'/validation.yml')
    );

Now, we can replace the usage of the static method and move all the validation
rules to ``validation.yml``:

.. code-block:: yaml

    # validation.yml
    Post:
      properties:
        title:
          - NotNull: ~
          - NotBlank: ~
        body:
          - Min: 100
