Var Dumper
==========

The *VarDumperServiceProvider* provides a mechanism that allows exploring then
dumping any PHP variable.

Parameters
----------

* **var_dumper.dump_destination**: A stream URL where dumps should be written
  to (defaults to ``null``).

Services
--------

* n/a

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\VarDumperServiceProvider());

.. note::

    Add the Symfony VarDumper Component as a dependency:

    .. code-block:: bash

        composer require symfony/var-dumper

Usage
-----

Adding the VarDumper component as a Composer dependency gives you access to the
``dump()`` PHP function anywhere in your code.

If you are using Twig, it also provides a ``dump()`` Twig function and a
``dump`` Twig tag.

The VarDumperServiceProvider is also useful when used with the Silex
WebProfiler as the dumps are made available in the web debug toolbar and in the
web profiler.
