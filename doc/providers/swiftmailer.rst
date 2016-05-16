Swiftmailer
===========

The *SwiftmailerServiceProvider* provides a service for sending email through
the `Swift Mailer <http://swiftmailer.org>`_ library.

You can use the ``mailer`` service to send messages easily. By default, it
will attempt to send emails through SMTP.

Parameters
----------

* **swiftmailer.use_spool**: A boolean to specify whether or not to use the
  memory spool, defaults to true.

* **swiftmailer.options**: An array of options for the default SMTP-based
  configuration.

  The following options can be set:

  * **host**: SMTP hostname, defaults to 'localhost'.
  * **port**: SMTP port, defaults to 25.
  * **username**: SMTP username, defaults to an empty string.
  * **password**: SMTP password, defaults to an empty string.
  * **encryption**: SMTP encryption, defaults to null. Valid values are 'tls', 'ssl', or null (indicating no encryption).
  * **auth_mode**: SMTP authentication mode, defaults to null. Valid values are 'plain', 'login', 'cram-md5', or null.

  Example usage::

    $app['swiftmailer.options'] = array(
        'host' => 'host',
        'port' => '25',
        'username' => 'username',
        'password' => 'password',
        'encryption' => null,
        'auth_mode' => null
    );

* **swiftmailer.sender_address**: If set, all messages will be delivered with
  this address as the "return path" address.

* **swiftmailer.delivery_addresses**: If not empty, all email messages will be
  sent to those addresses instead of being sent to their actual recipients. This
  is often useful when developing.

* **swiftmailer.delivery_whitelist**: Used in combination with
  ``delivery_addresses``. If set, emails matching any of these patterns will be
  delivered like normal, as well as being sent to ``delivery_addresses``.

Services
--------

* **mailer**: The mailer instance.

  Example usage::

    $message = \Swift_Message::newInstance();

    // ...

    $app['mailer']->send($message);

* **swiftmailer.transport**: The transport used for e-mail
  delivery. Defaults to a ``Swift_Transport_EsmtpTransport``.

* **swiftmailer.transport.buffer**: StreamBuffer used by
  the transport.

* **swiftmailer.transport.authhandler**: Authentication
  handler used by the transport. Will try the following
  by default: CRAM-MD5, login, plaintext.

* **swiftmailer.transport.eventdispatcher**: Internal event
  dispatcher used by Swiftmailer.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\SwiftmailerServiceProvider());

.. note::

    Add SwiftMailer as a dependency:

    .. code-block:: bash

        composer require swiftmailer/swiftmailer

Usage
-----

The Swiftmailer provider provides a ``mailer`` service::

    use Symfony\Component\HttpFoundation\Request;

    $app->post('/feedback', function (Request $request) use ($app) {
        $message = \Swift_Message::newInstance()
            ->setSubject('[YourSite] Feedback')
            ->setFrom(array('noreply@yoursite.com'))
            ->setTo(array('feedback@yoursite.com'))
            ->setBody($request->get('message'));

        $app['mailer']->send($message);

        return new Response('Thank you for your feedback!', 201);
    });

Usage in commands
~~~~~~~~~~~~~~~~~

By default, the Swiftmailer provider sends the emails using the ``KernelEvents::TERMINATE``
event, which is fired after the response has been sent. However, as this event
isn't fired for console commands, your emails won't be sent.

For that reason, if you send emails using a command console, it is recommended
that you disable the use of the memory spool (before accessing ``$app['mailer']``)::

    $app['swiftmailer.use_spool'] = false;

Alternatively, you can just make sure to flush the message spool by hand before
ending the command execution. To do so, use the following code::

    $app['swiftmailer.spooltransport']
        ->getSpool()
        ->flushQueue($app['swiftmailer.transport'])
    ;

Traits
------

``Silex\Application\SwiftmailerTrait`` adds the following shortcuts:

* **mail**: Sends an email.

.. code-block:: php

    $app->mail(\Swift_Message::newInstance()
        ->setSubject('[YourSite] Feedback')
        ->setFrom(array('noreply@yoursite.com'))
        ->setTo(array('feedback@yoursite.com'))
        ->setBody($request->get('message')));

For more information, check out the `Swift Mailer documentation
<http://swiftmailer.org>`_.
