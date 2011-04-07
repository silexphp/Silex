SessionExtension
================

The *SessionExtension* provides a service for storing data persistently
between requests.

Parameters
----------

* **session.storage.options**: An array of options that is passed to the
  constructor of the ``session.storage`` service.

Services
--------

* **session**: An instance of Symfony2's `Session
  <http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/Session.html>`_.

* **session.storage**: A service that is used for persistence of the
  session data.

Registering
-----------

::

    use Silex\Extension\SessionExtension;

    $app->register(new SessionExtension());

Usage
-----

The Session extension provides a ``session`` service. Here is an
example that authenticates a user and creates a session for him::

    use Symfony\Component\HttpFoundation\Response;

    $app->get('/login', function() use ($app) {
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

    $app->get('/account', function() use ($app) {
        if (null === $user = $app['session']->get('user')) {
            return $app->redirect('/login');
        }

        return "Welcome {$user['username']}!";
    });
