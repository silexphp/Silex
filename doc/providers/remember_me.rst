RememberMeServiceProvider
=========================

The *RememberMeServiceProvider* adds "Remember-Me" authentication to the
*SecurityServiceProvider*.

Parameters
----------

n/a

Services
--------

n/a

.. note::

    The service provider defines many other services that are used internally
    but rarely need to be customized.

Registering
-----------

Before registering this service provider, you must register the
*SecurityServiceProvider*::

    $app->register(new Silex\Provider\SecurityServiceProvider());
    $app->register(new Silex\Provider\RememberMeServiceProvider());

    $app['security.firewalls'] = array(
        'my-firewall' => array(
            'pattern'     => '^/secure$',
            'form'        => true,
            'logout'      => true,
            'remember_me' => array(
                'key'                => 'Choose_A_Unique_Random_Key',
                'always_remember_me' => true,
                /* Other options */
            ),
            'users' => array( /* ... */ ),
        ),
    );

Options
-------

* **key**: A secret key to generate tokens (you should generate a random
  string).

* **name**: Cookie name (default: ``REMEMBERME``).

* **lifetime**: Cookie lifetime (default: ``31536000`` ~ 1 year).

* **path**: Cookie path (default: ``/``).

* **domain**: Cookie domain (default: ``null`` = request domain).

* **secure**: Cookie is secure (default: ``false``).

* **httponly**: Cookie is HTTP only (default: ``true``).

* **always_remember_me**: Enable remember me (default: ``false``).

* **remember_me_parameter**: Name of the request parameter enabling remember_me
  on login. To add the checkbox to the login form. You can find more
  information in the `Symfony cookbook
  <http://symfony.com/doc/current/cookbook/security/remember_me.html>`_
  (default: ``_remember_me``).
