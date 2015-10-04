CsrfServiceProvider
===================

The *CsrfServiceProvider* provides a service for building forms in your
application with the Symfony2 Form component.

Parameters
----------

* none

Services
--------

* **csrf.token_manager**: An instance of an implementation of the
  `CsrfProviderInterface
  <http://api.symfony.com/master/Symfony/Component/Form/Extension/Csrf/CsrfProvider/CsrfProviderInterface.html>`_,
  defaults to a `DefaultCsrfProvider
  <http://api.symfony.com/master/Symfony/Component/Form/Extension/Csrf/CsrfProvider/DefaultCsrfProvider.html>`_.

Registering
-----------

.. code-block:: php

    use Silex\Provider\CsrfServiceProvider;

    $app->register(new CsrfServiceProvider());

.. note::

    Add the Symfony's `Serializer Component
    <http://symfony.com/doc/current/components/serializer.html>`_ as a
    dependency:

    .. code-block:: bash

        composer require symfony/security-csrf

Usage
-----

When the CSRF Service Provider is registered, all forms created via the Form
Service Provider are protected against CSRF by default.

You can also use the CSRF protection even without using the Symfony Form
component. If, for example, you're doing a DELETE action, you can check the
CSRF token::

.. code-block:: php

    use Symfony\Component\Security\Csrf\CsrfToken;

    $app['csrf.token_manager']->isTokenValid(new CsrfToken('token_id', 'TOKEN'));
