How to Create a Custom Authentication System with Guard
=======================================================

TODO: This cookbook will be inspired by the Symfony cookbook.
http://symfony.com/doc/current/cookbook/security/guard-authentication

Step 1) Create the Authenticator Class
--------------------------------------

TODO: Same as the Symfony cookbook, but Doctrine ORM is not recommanded with Silex.

Step 2) Configure the Authenticator
-----------------------------------

To finish this, register the class as a service:

.. code-block:: php

    $app['app.token_authenticator'] = function ($app) {
        return new App\Security\TokenAuthenticator();
    }


Finally, configure your firewalls key to use this authenticator:

.. code-block:: php

    $app['security.firewalls'] => array(
        'main' => array(
            'anonymous' => true,

            'guard' => array(
                'authenticators' => array(
                    'app.token_authenticator'
                ),
            ),
        ),
    );


