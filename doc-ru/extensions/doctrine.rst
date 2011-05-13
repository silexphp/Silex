DoctrineExtension
=================


Расширение *DoctrineExtension* предоставляет доступ к сервису `Doctrine DBAL
<http://www.doctrine-project.org/projects/dbal>`_ для более легкого доступа
к базам данных.

.. note::
    Расширение предоставляет доступ только к DBAL. Доступа к сервису ORM нет.    

Параметры
---------

* **db.options**: Массив настроек Doctrine DBAL.

  Доступны следующие настройки:

  * **driver**: Драйвер БД для использования, по-умолчанию ``pdo_mysql``.
    Может быть одним из следующих: ``pdo_mysql``, ``pdo_sqlite``, ``pdo_pgsql``,
    ``pdo_oci``, ``oci8``, ``ibm_db2``, ``pdo_ibm``, ``pdo_sqlsrv``.

  * **dbname**: Имя БД для подключения.

  * **host**: Хост БД для подключения. По-умолчанию localhost.

  * **user**: Имя пользователя для подключения к БД. По-умолчанию root.

  * **password**: Пароль доступа к БД.

  * **path**: Необходима только для драйвера ``pdo_sqlite``, указывает путь к базе 
    SQLite.

  Эти и другие настройки описаны в документации по конфигурированию `Doctrine DBAL
  <http://www.doctrine-project.org/docs/dbal/2.0/en/reference/configuration.html>`_.

* **db.dbal.class_path** (необязательный): Путь к библиотеке где расположен Doctrine DBAL.

* **db.common.class_path** (необязательный): Путь к Doctrine Common.

Сервисы
-------

* **db**: Экземпляр ``Doctrine\DBAL\Connection``.

* **db.config**: Объект настройки Doctrine. По-умолчанию пустой 
    ``Doctrine\DBAL\Configuration``.

* **db.event_manager**: Менеджер событий Doctrine.

Регистрация
-----------

Удостоверьтесь, что поместили копии *Doctrine DBAL* в каталог ``vendor/doctrine-dbal``
и *Doctrine Common* в ``vendor/doctrine-common``.

::

    use Silex\Extension\DoctrineExtension;

    $app->register(new DoctrineExtension(), array(
        'db.options'            => array(
            'driver'    => 'pdo_sqlite',
            'path'      => __DIR__.'/app.db',
        ),
        'db.dbal.class_path'    => __DIR__.'/vendor/doctrine-dbal/lib',
        'db.common.class_path'  => __DIR__.'/vendor/doctrine-common/lib',
    ));

Использование
-------------

Расширение Doctrine предоставляет доступ к сервису ``db``.
Ниже представлен пример использования:

    $app->get('/blog/show/{id}', function ($id) use ($app) {
        $sql = "SELECT * FROM posts WHERE id = ?";
        $post = $app['db']->fetchAssoc($sql, array((int) $id));

        return  "<h1>{$post['title']}</h1>".
                "<p>{$post['body']}</p>";
    });

Для получения более подробной информации смотрите `Doctrine DBAL documentation
<http://www.doctrine-project.org/docs/dbal/2.0/en/>`_.