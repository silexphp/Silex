FilesystemServiceProvider
=========================

The *FilesystemServiceProvider* provides an OS-aware service for filesystem
operations such as `mkdir`, `touch` or other usefull operations.

Parameters
----------

none

Services
--------

* **filesystem**: An instance of `Filesystem
  <http://api.symfony.com/2.0/Symfony/Component/Filesystem/Filesystem.html>`_.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\FilesystemServiceProvider());

.. note::

    The Symfony Filesystem Component comes with the "fat" Silex archive but not
    with the regular one. If you are using Composer, add it as a dependency to
    your ``composer.json`` file:

    .. code-block:: json

        "require": {
            "symfony/filesystem": "2.1.*"
        }

Usage
-----

Please consult the `Symfony Filesystem documentation
<http://symfony.com/doc/master/components/filesystem.html>`_ to know all the
methods provided by this provider.
