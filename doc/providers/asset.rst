Asset
=====

The *AssetServiceProvider* provides a way to manage URL generation and
versioning of web assets such as CSS stylesheets, JavaScript files and image
files.

Parameters
----------

* **assets.version**: Default version for assets.

* **assets.format_version** (optional): Default format for assets.

* **assets.named_packages** (optional): Named packages. Keys are the package
  names and values the configuration (supported keys are ``version``,
  ``version_format``, ``base_urls``, and ``base_path``).

Services
--------

* **assets.packages**: The asset service.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\AssetServiceProvider(), array(
        'assets.version' => 'v1',
        'assets.version_format' => '%s?version=%s',
        'assets.named_packages' => array(
            'css' => array('version' => 'css2', 'base_path' => '/whatever-makes-sense'),
            'images' => array('base_urls' => array('https://img.example.com')),
        ),
    ));

.. note::

    Add the Symfony Asset Component as a dependency:

    .. code-block:: bash

        composer require symfony/asset

    If you want to use assets in your Twig templates, you must also install the
    Symfony Twig Bridge:

    .. code-block:: bash

        composer require symfony/twig-bridge

Usage
-----

The AssetServiceProvider is mostly useful with the Twig provider:

.. code-block:: jinja

    {{ asset('/css/foo.png') }}
    {{ asset('/css/foo.css', 'css') }}
    {{ asset('/img/foo.png', 'images') }}

    {{ asset_version('/css/foo.png') }}

For more information, check out the `Asset Component documentation
<https://symfony.com/doc/current/components/asset/introduction.html>`_.
