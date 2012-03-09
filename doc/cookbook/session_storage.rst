How to use PdoSessionStorage to store sessions in the database
==============================================================

By default, the :doc:`SessionServiceProvider <providers/session>` write sessions
informations to file with the NativeFileSessionStorage of Symfony2. Most medium
to large websites use a database to store the session values instead of files,
because databases are easier to use and scale in a multi-webserver environment.

Symfony2 has multiple session storage solutions. For database, its called
`PdoSessionStorage <http://api.symfony.com/master/Symfony/Component/HttpFoundation/SessionStorage/PdoSessionStorage.html>
To use it, you need to replace the ``session.storage`` service in your application.

Example
-------

    use Symfony\Component\HttpFoundation\Session\Storage\PdoSessionStorage;

    $app->register(new Silex\Provider\SessionServiceProvider());

    $app['pdo.dsn'] = 'mysql:dbname=mydatabase';
    $app['pdo.user'] = 'myuser';
    $app['pdo.password'] = 'mypassword';

    $app['pdo.db_options'] = array(
        'db_table'      => 'session',
        'db_id_col'     => 'session_id',
        'db_data_col'   => 'session_value',
        'db_time_col'   => 'session_time',
    );

    $app['pdo'] = $app->share(function () use ($app) {
        return new PDO(
            $app['pdo.dsn'],
            $app['pdo.user'],
            $app['pdo.password']
        );
    });

    $app['session.storage'] = $app->share(function () use ($app) {
        return new PdoSessionStorage(
            $app['pdo'],
            $app['pdo.db_options'],
            $app['session.storage.options']
        );
    });


Database structure
------------------

PdoSessionStorage needs a database table with 3 columns:

* ``session_id``: ID column (VARCHAR(255) or larger)
* ``session_value``: Value column (TEXT or CLOB)
* ``session_time``: Time column (INTEGER)

Examples of SQL statements to create the session table on `Symfony2 cookbook
<http://symfony.com/doc/current/cookbook/configuration/pdo_session_storage.html>`
