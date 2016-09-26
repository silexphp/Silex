How to Create a Custom Authentication System with Guard
=======================================================

Whether you need to build a traditional login form, an API token
authentication system or you need to integrate with some proprietary
single-sign-on system, the Guard component can make it easy... and fun!

In this example, you'll build an API token authentication system and
learn how to work with Guard.

Step 1) Create the Authenticator Class
--------------------------------------

Suppose you have an API where your clients will send an X-AUTH-TOKEN
header on each request. This token is composed of the username followed
by a password, separated by a colon (e.g. ``X-AUTH-TOKEN: coolguy:awesomepassword``).
Your job is to read this, find the associated user (if any) and check
the password.

To create a custom authentication system, just create a class and make
it implement GuardAuthenticatorInterface. Or, extend the simpler
AbstractGuardAuthenticator. This requires you to implement six methods:

.. code-block:: php

    <?php

    namespace App\Security;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\Security\Core\User\UserInterface;
    use Symfony\Component\Security\Core\User\UserProviderInterface;
    use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Exception\AuthenticationException;

    class TokenAuthenticator extends AbstractGuardAuthenticator
    {
        private $encoderFactory;

        public function __construct(EncoderFactoryInterface $encoderFactory)
        {
            $this->encoderFactory = $encoderFactory;
        }

        public function getCredentials(Request $request)
        {
            // Checks if the credential header is provided
            if (!$token = $request->headers->get('X-AUTH-TOKEN')) {
                return;
            }

            // Parse the header or ignore it if the format is incorrect.
            if (false === strpos($token, ':')) {
                return;
            }
            list($username, $secret) = explode(':', $token, 2);

            return array(
                'username' => $username,
                'secret' => $secret,
            );
        }

        public function getUser($credentials, UserProviderInterface $userProvider)
        {
            return $userProvider->loadUserByUsername($credentials['username']);
        }

        public function checkCredentials($credentials, UserInterface $user)
        {
            // check credentials - e.g. make sure the password is valid
            // return true to cause authentication success

            $encoder = $this->encoderFactory->getEncoder($user);

            return $encoder->isPasswordValid(
                $user->getPassword(),
                $credentials['secret'],
                $user->getSalt()
            );
        }

        public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
        {
            // on success, let the request continue
            return;
        }

        public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
        {
            $data = array(
                'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),

                // or to translate this message
                // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
            );

            return new JsonResponse($data, 403);
        }

        /**
         * Called when authentication is needed, but it's not sent
         */
        public function start(Request $request, AuthenticationException $authException = null)
        {
            $data = array(
                // you might translate this message
                'message' => 'Authentication Required',
            );

            return new JsonResponse($data, 401);
        }

        public function supportsRememberMe()
        {
            return false;
        }
    }


Step 2) Configure the Authenticator
-----------------------------------

To finish this, register the class as a service:

.. code-block:: php

    $app['app.token_authenticator'] = function ($app) {
        return new App\Security\TokenAuthenticator($app['security.encoder_factory']);
    };


Finally, configure your `security.firewalls` key to use this authenticator:

.. code-block:: php

    $app['security.firewalls'] => array(
        'main' => array(
            'guard' => array(
                'authenticators' => array(
                    'app.token_authenticator'
                ),

                // Using more than 1 authenticator, you must specify
                // which one is used as entry point.
                // 'entry_point' => 'app.token_authenticator',
            ),
            // configure where your users come from. Hardcode them, or load them from somewhere
            // http://silex.sensiolabs.org/doc/providers/security.html#defining-a-custom-user-provider
            'users' => array(
                'victoria' => array('ROLE_USER', 'randomsecret'),
            ),
            // 'anonymous' => true
        ),
    );

.. note::
    You can use many authenticators, they are executed by the order
    they are configured.

You did it! You now have a fully-working API token authentication
system. If your homepage required ROLE_USER, then you could test it
under different conditions:

.. code-block:: bash

    # test with no token
    curl http://localhost:8000/
    # {"message":"Authentication Required"}

    # test with a bad token
    curl -H "X-AUTH-TOKEN: alan" http://localhost:8000/
    # {"message":"Username could not be found."}

    # test with a working token
    curl -H "X-AUTH-TOKEN: victoria:randomsecret" http://localhost:8000/
    # the homepage controller is executed: the page loads normally

For more details read the Symfony cookbook entry on
`How to Create a Custom Authentication System with Guard <http://symfony.com/doc/current/cookbook/security/guard-authentication.html>`_.
