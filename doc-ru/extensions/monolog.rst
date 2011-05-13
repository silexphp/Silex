MonologExtension
================

Расширение *MonologExtension* предоставляет доступ механизму
журналирования с помощью библиотеки Джорди Бодджиано - 
`Monolog <https://github.com/Seldaek/monolog>`_

The *MonologExtension* provides a default logging mechanism
through Jordi Boggiano's `Monolog <https://github.com/Seldaek/monolog>`_
library.

Библиотека будет записывать все запросы и ошибки, и позволяет добавить
информацию об отладке в ваше приложение, таким образом вам не придется
использовать ``var_dump`` так часто.  Можно использовать её старшую 
версию называющуюся ``tail --f``.

It will log requests and errors and allow you to add debug
logging to your application, so you don't have to use
``var_dump`` so much anymore. You can use the grown-up
version called ``tail -f``.

Параметры
---------
Parameters
----------

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

* **monolog.logfile**: File where logs are written to.

* **monolog.class_path** (optional): Path to where the
  Monolog library is located.

* **monolog.level** (optional): Level of logging defaults
  to ``DEBUG``. Must be one of ``Logger::DEBUG``, ``Logger::INFO``,
  ``Logger::WARNING``, ``Logger::ERROR``. ``DEBUG`` will log
  everything, ``INFO`` will log everything except ``DEBUG``,
  etc.

* **monolog.name** (optional): Name of the monolog channel,
  defaults to ``myapp``.

Сервисы
-------
Services
--------

* **monolog**: Экземпляр логера.

Пример использования::

    $app['monolog']->addDebug('Testing the Monolog logging.');


* **monolog**: The monolog logger instance.

  Example usage::

    $app['monolog']->addDebug('Testing the Monolog logging.');

* **monolog.configure**: Защищенное хранилище которое принимает в качестве аргумента логер.
  Можно его переопределить если вас не устраивает его поведения по-умолчнаию.

* **monolog.configure**: Protected closure that takes the
  logger as an argument. You can override it if you do not
  want the default behavior.

Registering
-----------

Make sure you place a copy of *Monolog* in the ``vendor/monolog``
directory.

::

    use Silex\Extension\MonologExtension;

    $app->register(new MonologExtension(), array(
        'monolog.logfile'       => __DIR__.'/development.log',
        'monolog.class_path'    => __DIR__.'/vendor/monolog/src',
    ));
