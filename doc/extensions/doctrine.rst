DoctrineExtension
=================

The *DoctrineExtension* provides integration with the `Doctrine DBAL
<http://www.doctrine-project.org/projects/dbal>`_ for easy database acccess.

.. note::

    There is only a Doctrine DBAL. An ORM service is **not** supplied.

Parameters
----------

* **dbal.options**: Array of Doctrine DBAL options.

  These options are available:

  * **driver**: The database driver to use, defaults to ``pdo_mysql``.
    Can be any of: ``pdo_mysql``, ``pdo_sqlite``, ``pdo_pgsql``,
    ``pdo_oci``, ``oci8``, ``ibm_db2``, ``pdo_ibm``, ``pdo_sqlsrv``.

  * **dbname**: The name of the database to connect to.

  * **host**: The host of the database to connect to. Defaults to
    localhost.

  * **user**: The user of the database to connect to. Defaults to
    root.

  * **password**: The password of the database to connect to.

  * **path**: Only relevant for ``pdo_sqlite``, specifies the path to
    the SQLite database.

  * **default**: (optional) When using multiple databases, allows you 
    to set the default connection to one other than the first 
    connection

  These and additional options are described in detail in the `Doctrine DBAL
  configuration documentation <http://www.doctrine-project.org/docs/dbal/2.0/en/reference/configuration.html>`_.

* **dbal.dbal.class_path** (optional): Path to where the
  Doctrine DBAL is located.

* **dbal.common.class_path** (optional): Path to where
  Doctrine Common is located.

Services
--------

* **dbal**: The database connection, instance of
  ``Doctrine\DBAL\Connection``.

* **dbal.config**: Configuration object for Doctrine. Defaults to
  an empty ``Doctrine\DBAL\Configuration``.

* **dbal.event_manager**: Event Manager for Doctrine.

Registering
-----------

Make sure you place a copy of *Doctrine DBAL* in ``vendor/doctrine-dbal``
and *Doctrine Common* in ``vendor/doctrine-common``::

    $app->register(new Silex\Extension\DoctrineExtension(), array(
        'dbal.options'            => array(
            'driver'    => 'pdo_sqlite',
            'path'      => __DIR__.'/app.db',
        ),
        'dbal.dbal.class_path'    => __DIR__.'/vendor/doctrine-dbal/lib',
        'dbal.common.class_path'  => __DIR__.'/vendor/doctrine-common/lib',
    ));

Usage
-----

The Doctrine extension provides a ``db`` service. Here is a usage
example::

    $app->get('/blog/show/{id}', function ($id) use ($app) {
        $sql = "SELECT * FROM posts WHERE id = ?";
        $post = $app['dbal']->fetchAssoc($sql, array((int) $id));

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });


Using multiple databases
------------------------

The Doctrine extension can allow access to multiple databases.  In order
configure these data sources you must remove the **db.options** from 
your extension registration, and replace it with an array named **dbs**.

Each key of the dbs array should contain a configuration of options.

Registering multiple database connections::

    $app->register(new Silex\Extension\DoctrineExtension(), array(
        'dbal.dbs' => array (
            'sqlite' => array(
                'driver'    => 'pdo_sqlite',
                'path'      => __DIR__.'/app.db',
            ),
            'mysql_read' => array(
                'driver'    => 'pdo_mysql',
                'host'      => 'mysql_read.someplace.tld'
                'dbname'    => 'my_database',
                'user'      => 'my_username',
                'password'  => 'my_password',
            ),
            'mysql_write' => array(
                'driver'    => 'pdo_mysql',
                'host'      => 'mysql_write.someplace.tld'
                'dbname'    => 'my_database',
                'user'      => 'my_username',
                'password'  => 'my_password',
            ),
        ),
        'dbal.dbal.class_path'    => __DIR__.'/vendor/doctrine-dbal/lib',
        'dbal.common.class_path'  => __DIR__.'/vendor/doctrine-common/lib',
    ));

By default, the first connection registered is the default.  This can simply be accessed as you would if there was only one connection.  Given the above DB registration these two lines are equal:

	$app['dbal']->fetchAssoc('SELECT * FROM table');
	
	$app['dbal.connection.sqlite']->fetchAssoc('SELECT * FROM table');

The default connection can be selected by setting the **default** option toggle.

Using multiple connections::

    $app->get('/joined/{searchOne}/{searchTwo}, function ($searchOne, $searchTwo) use ($app)) {
        $sqliteQuery = "SELECT * FROM table_one WHERE id = ?";
        $one = $app['dbal.connection.sqlite']->fetchAssoc($sqliteQuery, array((int) $searchOne));
        
        $mysqlQuery = "SELECT * FROM table_two WHERE id = ?";
        $two = $app['dbal.connection.mysql_read']->fetchAssoc($mysqlQuery, array((int) $searchTwo));

        $mysqlUpdate = "UPDATE table_two SET value = ? WHERE id = ?";
        $app['dbal.connection.mysql_write']->execute($mysqlUpdate, array((int) $searchTwo, 'newValue'));
        
        return  "<h1>{$one['column_from_sqlite']}</h1>".
                "<p>{$two['column_from_mysql']}</p>";
        
    });

For more information, consult the `Doctrine DBAL documentation
<http://www.doctrine-project.org/docs/dbal/2.0/en/>`_.
