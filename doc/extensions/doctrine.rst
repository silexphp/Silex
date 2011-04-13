DoctrineExtension
================

The *DoctrineExtension* provides a default `Doctrine2 <http://www.doctrine-project.org>`_ DBAL Connection and an EntityManager.


Registering
-----------

.. code-block:: php

    use Silex\Extension\DoctrineExtension;

    $app->register(new DoctrineExtension(), array(
        'doctrine.dbal.connection_options' => array(,
            'driver' => 'pdo_sqlite',
            'path' => ':memory'
        ),
        'doctrine.orm' => true
    ));

    // if you want to autoload your entities, use the autoloader service:
    $app['autoloader']->registerNamespace('Entity', __DIR__);


Parameters
----------

* **doctrine.dbal.connection_options**: an array of connection parameters for DBAL of the form:

.. code-block:: php

    'doctrine.dbal.connection_options' => array(
        'driver' => 'pdo_sqlite',
        'path' => ':memory'
    ),

or:

.. code-block:: php

    'doctrine.dbal.connection_options' => array(
        'driver' => 'pdo_mysql',
        'dbname' => 'my_database_name',
        'host' => 'localhost',
        'user' => 'root',
        'password' => null
    ),

This parameter activates the ``doctrine.dbal.connection`` service.

These above are simple examples. Full `documentation <http://www.doctrine-project.org/docs/dbal/2.0/en/reference/configuration.html>`_ is available.

* **doctrine.orm** (optional): a boolean to activate the ``doctrine.orm.em`` service. Defaults to ``false``.

* **doctrine.orm.entities** (optional): an array of mapping configurations of the form:

.. code-block:: php

    'doctrine.orm.entities' => array(
        array('type' => 'yml', 'path' => '/path/to/yml/files', 'namespace' => 'My\\Entity'),

        array('type' => 'annotation', 'path' => array(
            '/path/to/Entities',
            '/path/to/another/dir/for/the/same/namespace'
        ), 'namespace' => 'Entity'),

        array('type' => 'annotation', 'path' => '/path/to/another/dir/with/entities', 'namespace' => 'Acme\\Entity'),

        array('type' => 'xml', 'path' => '/path/to/xml/files', 'namespace' => 'Your\\Entity')
    )

This is an advanced configuration example which replaces the default one:

.. code-block:: php

    array('type' => 'annotation', 'path' => 'Entity', 'namespace' => 'Entity')

Default behavior will search annotated ``Entities`` in the ``Entity`` directory.

* **doctrine.orm.proxies_dir** (optional): Path to where the
  doctrine Proxies are generated. Default is ``cache/doctrine/Proxy``.

* **doctrine.orm.proxies_namespace** (optional): Namespace of generated
  doctrine Proxies. Default is ``DoctrineProxy``.

* **doctrine.orm.auto_generate_proxies** (optional): Tell Doctrine wether it should generate proxies automatically. Default is ``true``.

* **doctrine.orm.class_path** (optional): Path to where the
  Doctrine\\ORM library is located.

* **doctrine.common.class_path** (optional): Path to where the
  Doctrine\\Common library is located.

* **doctrine.dbal.class_path** (optional): Path to where the
  Doctrine\\DBAL library is located.

Services
--------

* **doctrine.dbal.connection**: The ``Doctrine\DBAL\Connection`` instance.
* **doctrine.dbal.event_manager**: The ``Doctrine\DBAL\EventManager`` instance.
* **doctrine.configuration**: The ``Doctrine\ORM\Configuration`` instance or ``Doctrine\DBAL\Configuration`` if ``doctrine.orm`` is false.
* **doctrine.orm.em**: The ``Doctrine\ORM\EntityManager`` instance.


Usage
-----

* DBAL

.. code-block:: php

    $categories = $app['doctrine.dbal.connection']->query('SELECT * FROM category')->fetchAll();

* ORM

.. code-block:: php

    $category = $app['doctrine.orm.em']->getRepository('Acme\Entity\Category')->findOneBy(array('name' => 'Category A'));


* Event subscribers, Behaviors

This is an example of how to add a Timestampable behavior to Doctrine. ( http://gediminasm.org/article/timestampable-behavior-extension-for-doctrine-2 )

.. code-block:: php

    // if you need autoloading of external lib
    $app['autoloader']->registerNamespace('Gedmo', __DIR__.'/vendor/Gedmo/DoctrineExtensions/lib');

    $timestampableListener = new \Gedmo\Timestampable\TimestampableListener(); 
    $app['doctrine.dbal.event_manager']->addEventSubscriber($timestampableListener);


