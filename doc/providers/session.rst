SessionServiceProvider
======================

The *SessionServiceProvider* provides a service for storing data persistently
between requests.

Parameters
----------

* **session.storage.save_path** (optional): The path for the
  ``NativeFileSessionHandler``, defaults to the value of
  ``sys_get_temp_dir()``.

* **session.storage.options**: An array of options that is passed to the
  constructor of the ``session.storage`` service.

  In case of the default ``NativeSessionStorage``, the possible options are:

  * **name**: The cookie name (_SESS by default)
  * **id**: The session id (null by default)
  * **cookie_lifetime**: Cookie lifetime
  * **path**: Cookie path
  * **domain**: Cookie domain
  * **secure**: Cookie secure (HTTPS)
  * **httponly**: Whether the cookie is http only

  However, all of these are optional. Sessions last as long as the browser
  is open. To override this, set the ``lifetime`` option.

Services
--------

* **session**: An instance of Symfony2's `Session
  <http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Session.html>`_.

* **session.storage**: A service that is used for persistence of the
  session data. Defaults to a ``NativeSessionStorage``
  
* **session.storage.handler**: A service that is used by the ``session.storage``
  for data access. Defaults to a ``NativeFileSessionHandler`` storage handler.

Registering
-----------

::

    $app->register(new Silex\Provider\SessionServiceProvider());

Usage
-----

The Session provider provides a ``session`` service. Here is an
example that authenticates a user and creates a session for him::

    use Symfony\Component\HttpFoundation\Response;

    $app->get('/login', function () use ($app) {
        $username = $app['request']->server->get('PHP_AUTH_USER', false);
        $password = $app['request']->server->get('PHP_AUTH_PW');

        if ('igor' === $username && 'password' === $password) {
            $app['session']->start();
            $app['session']->set('user', array('username' => $username));
            return $app->redirect('/account');
        }

        $response = new Response();
        $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
        $response->setStatusCode(401, 'Please sign in.');
        return $response;
    });

    $app->get('/account', function () use ($app) {
        $app['session']->start();
        if (null === $user = $app['session']->get('user')) {
            return $app->redirect('/login');
        }

        return "Welcome {$user['username']}!";
    });
