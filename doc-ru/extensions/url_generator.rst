UrlGeneratorExtension
=====================

Расширение *UrlGeneratorExtension* представляет собой генератор 
URL для именованых маршрутов.

Параметры
---------
Отстутсвуют.

Сервисы
-------

* **url_generator**: Экземпляр `UrlGenerator
  <http://api.symfony.com/2.0/Symfony/Component/Routing/Generator/UrlGenerator.html>`_,
  использует `RouteCollection
  <http://api.symfony.com/2.0/Symfony/Component/Routing/RouteCollection.html>`_
  который доступен через сервис ``routes``.
  Содержит метод ``generate``, который получает маршрут в качестве первого аргумента, 
  и массив параметров в качестве второго.

Регистрация
-----------

::

    use Silex\Extension\UrlGeneratorExtension;

    $app->register(new UrlGeneratorExtension());

Использование
-----

Расширение UrlGenerator предоставляет доступ к сервису ``url_generator``.

::

    $app->get('/', function () {
        return 'welcome to the homepage';
    })
    ->bind('homepage');

    $app->get('/hello/{name}', function ($name) {
        return "Hello $name!";
    })
    ->bind('hello');

    $app->get('/navigation', function () use ($app) {
        return '<a href="'.$app['url_generator']->generate('homepage').'">Home</a>'.
               ' | '.
               '<a href="'.$app['url_generator']->generate('hello', array('name' => 'Igor')).'">Hello Igor</a>';
    });
