MonologExtension
================

Расширение *MonologExtension* предоставляет доступ механизму
журналирования с помощью библиотеки Джорди Бодджиано - 
`Monolog <https://github.com/Seldaek/monolog>`_

Библиотека будет записывать все запросы и ошибки, и позволяет добавить
информацию об отладке в ваше приложение, таким образом вам не придется
использовать ``var_dump`` так часто.  Можно использовать её старшую 
версию - ``tail --f``.

Параметры
---------

* **monolog.logfile**: Файл в который будет писаться журнал.

* **monolog.class_path** (необязательный): Путь к библиотеке Monolog.

* **monolog.level** (необязательный): Уровень журналирования 
  по-умолчанию установлен в ``DEBUG``. Может быть одним из следующих
  ``Logger::DEBUG``, ``Logger::INFO``, ``Logger::WARNING``, 
  ``Logger::ERROR``.  уровень ``DEBUG`` ведет лог всех сообщений.
, ``INFO`` ведет лог всех сообщений за ислючением ``DEBUG``,
  etc.

* **monolog.name** (необязательный): Имя канала в который пишет monolog,
  по-умолчанию ``myapp``.

Сервисы
-------

* **monolog**: Экземпляр логера.

Пример использования::

    $app['monolog']->addDebug('Testing the Monolog logging.');


* **monolog.configure**: Защищенное хранилище которое принимает в 
  качестве аргумента логер. Можно его переопределить если вас не 
  устраивает его поведения по-умолчнаию.

Регистрация расширения
----------------------

Удостоверьтесь, что поместили копию библиотеки *Monolog*
в каталог ``vendor/monolog``

::

    use Silex\Extension\MonologExtension;

    $app->register(new MonologExtension(), array(
        'monolog.logfile'       => __DIR__.'/development.log',
        'monolog.class_path'    => __DIR__.'/vendor/monolog/src',
    ));
