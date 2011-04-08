doctrineOrmExtension
================

The *DoctrineOrmExtension* provides a default `Doctrine2 <http://www.doctrine-project.org>`_ EntityManager.

Parameters
----------

* **doctrine.orm.connection_options**: an array of connection parameters for DBAL of the form:

.. code-block:: php
    'doctrine.orm.connection_options' => array(
        'driver' => 'pdo_sqlite',
        'path' => ':memory'
    ),

or:
.. code-block:: php

    'doctrine.orm.connection_options' => array(
        'driver' => 'pdo_mysql',
        'dbname' => 'my_database_name',
        'host' => 'localhost',
        'user' => 'root',
        'password' => null
    ),

These above are simple examples. Full `documentation <http://www.doctrine-project.org/docs/dbal/2.0/en/reference/configuration.html>`_ is available.

* **doctrine.orm.entities** (optional): an array of mapping configurations of the form:

.. code-block:: php
    'doctrine.orm.entities' => array(
        array('type' => 'xml', 'path' => '/path/to/yml/files'),

        array('type' => 'annotation', 'path' => array(
            '/path/to/Entities',
            '/path/to/another/dir/for/the/same/namespace'
        ), 'namespace' => 'Entity'),

        array('type' => 'annotation', 'path' => '/path/to/another/dir/with/entities', 'namespace' => 'Acme\\Entity'),

        array('type' => 'xml', 'path' => '/path/to/xml/files')
    )

This is an advanced configuration example which replaces the default one:

.. code-block:: php
    array('type' => 'annotation', 'path' => 'Entity', 'namespace' => 'Entity')

Default behavior will search annotated ``Entities`` in the ``Entity`` directory.

* **doctrine.orm.proxies_dir** (optional): Path to where the
  doctrine Proxies are generated. Default is ``cache/doctrine/Proxy``.

* **doctrine.orm.proxies_namespace** (optional): Namespace of generated
  doctrine Proxies. Default is ``DoctrineProxy``.

* **doctrine.orm.auto_generate_proxies** (optional): Tell Doctrine wether it should generate proxies automaitcally. Default is ``true``.

* **doctrine.orm.class_path** (optional): Path to where the
  Doctrine\ORM library is located.

* **doctrine.common.class_path** (optional): Path to where the
  Doctrine\Common library is located.

* **doctrine.dbal.class_path** (optional): Path to where the
  Doctrine\DBAL library is located.

Services
--------

* **doctrine.orm.em**: The ``doctrine\ORM\EntityManager`` instance.

  Example usage::

    $category = $app['doctrine.orm.em']->getRepository('Entity\Category')->findOneBy(array('name' => 'Category A'));


Registering
-----------

.. code-block:: php

    use Silex\Extension\DoctrineOrmExtension;

    $app->register(new DoctrineOrmExtension(), array(
        'monolog.connection_options' => array(,
            'driver' => 'pdo_sqlite',
            'path' => ':memory'
        )
    ));
