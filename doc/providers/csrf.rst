CSRF
====

The *CsrfServiceProvider* provides a service for building forms in your
application with the Symfony Form component.

Parameters
----------

* **csrf.session_namespace** (optional): The namespace under which the token
  is stored in the session. Defaults to ``_csrf``.

Services
--------

* **csrf.token_manager**: An instance of an implementation of the
  `CsrfTokenManagerInterface
  <http://api.symfony.com/master/Symfony/Component/Security/Csrf/CsrfTokenManagerInterface.html>`_,

Registering
-----------

.. code-block:: php

    use Silex\Provider\CsrfServiceProvider;

    $app->register(new CsrfServiceProvider());

.. note::

    Add the Symfony's `Security CSRF Component
    <http://symfony.com/doc/current/components/security/index.html>`_ as a
    dependency:

    .. code-block:: bash

        composer require symfony/security-csrf

Usage
-----

When the CSRF Service Provider is registered, all forms created via the Form
Service Provider are protected against CSRF by default.

You can also use the CSRF protection without using the Symfony Form component.
If, for example, you're doing a DELETE action, create a CSRF token to use in
your code::

    use Symfony\Component\Security\Csrf\CsrfToken;
    $csrfToken = $app['csrf.token_manager']->getToken('token_id'); //'TOKEN'

Then check it::

    $app['csrf.token_manager']->isTokenValid(new CsrfToken('token_id', 'TOKEN'));
