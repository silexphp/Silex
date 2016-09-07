SessionExtension
================

Модуль *SessionExtension* предназначен для хранение данных 
между запросами.

Параметры
---------

* **session.storage.options**: массив параметров которые передаются конструктору сервиса
  ``session.storage``

Сервисы
-------

* **session**: Экземпляр компонента `Session
  <http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/Session.html>`_
  из Symfony2.

* **session.storage**: сервис, который используется для хранения
   данных сессии.

Регистрация модуля
------------------

::

    use Silex\Extension\SessionExtension;

    $app->register(new SessionExtension());

Использование
-------------

Модуль Session предоставляет доступ  к сервису ``session``.
Ниже приведен пример, в котором пользователь аутентифицируется
и создает сессию для него::

    use Symfony\Component\HttpFoundation\Response;

    $app->get('/login', function () use ($app) {
        $username = $app['request']->server->get('PHP_AUTH_USER', false);
        $password = $app['request']->server->get('PHP_AUTH_PW');

        if ('igor' === $username && 'password' === $password) {
            $app['session']->set('user', array('username' => $username));
            return $app->redirect('/account');
        }

        $response = new Response();
        $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
        $response->setStatusCode(401, 'Please sign in.');
        return $response;
    });

    $app->get('/account', function () use ($app) {
        if (null === $user = $app['session']->get('user')) {
            return $app->redirect('/login');
        }

        return "Welcome {$user['username']}!";
    });

