Asset
=====

The *AssetServiceProvider* provides a way to manage URL generation and
versioning of web assets such as CSS stylesheets, JavaScript files and image
files.

Parameters
----------

* **assets.version**: Default version for assets.

* **assets.version_format** (optional): Default format for assets.

* **assets.base_path**: Default path to prepend to all assets without a package.

* **assets.base_urls**: (Alternative to ``assets.base_path``) List of base URLs
  to choose from to prepend to assets without a package.

* **assets.named_packages** (optional): Named packages. Keys are the package
  names and values the configuration (supported keys are ``version``,
  ``version_format``, ``base_urls``, and ``base_path``).

* **assets.json_manifest_path** (optional): Absolute path to a `JSON version manifest
  <https://symfony.com/blog/new-in-symfony-3-3-manifest-based-asset-versioning>`_.

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

The AssetServiceProvider is mostly useful with the Twig provider using the
``asset()`` method. It takes two arguments. In the case of named
packages, the first is the path relative to the base_path specified in the
package definition and the second is the package name. For unmamed packages,
there is only one argument, the path relative to the assets folder:

.. code-block:: jinja

    {{ asset('/css/foo.png') }}
    {{ asset('/css/foo.css', 'css') }}
    {{ asset('/img/foo.png', 'images') }}

    {{ asset_version('/css/foo.png') }}

For more information, check out the `Asset Component documentation
<https://symfony.com/doc/current/components/asset/introduction.html>`_.
