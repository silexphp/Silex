SwiftmailerExtension
====================

The *SwiftmailerExtension* provides a service for sending
email through the `Swift Mailer <http://swiftmailer.org>`_
library.

You can use the ``mailer`` service to send messages easily.
By default, it will attempt to send emails through SMTP.

Parameters
----------

* **swiftmailer.options**: An array of options for the default
  SMTP-based configuration.

  The following options can be set:

  * **host**: SMTP hostname, defaults to 'localhost'.
  * **port**: SMTP port, defaults to 25.
  * **username**: SMTP username, defaults to an empty string.
  * **password**: SMTP password, defaults to an empty string.
  * **encryption**: SMTP encryption, defaults to null.
  * **auth_mode**: SMTP authentication mode, defaults to null.

* **swiftmailer.class_path** (optional): Path to where the
  Swift Mailer library is located.

Services
--------

* **mailer**: The mailer instance.

  Example usage::

    $message = \Swift_Message::newInstance();

    // ...

    $app['mailer']->send($message);

* **swiftmailer.transport**:

* **swiftmailer.transport.buffer**:

* **swiftmailer.transport.authhandler**:

* **swiftmailer.transport.eventdispatcher**:

Registering
-----------

Make sure you place a copy of *Swift Mailer* in the ``vendor/swiftmailer``
directory.

::

    $app->register(new Silex\Extension\SwiftmailerExtension(), array(
        'swiftmailer.class_path'  => __DIR__.'/vendor/swiftmailer/lib',
    ));

.. note::

    Swift Mailer is not compiled into the ``silex.phar`` file. You have to
    add your own copy of Swift Mailer to your application.

Usage
-----

The Swiftmailer extension provides a ``mailer`` service.

::

    $app->post('/feedback', function () use ($app) {
        $request = $app['request'];

        $message = \Swift_Message::newInstance()
            ->setSubject('[YourSite] Feedback')
            ->setFrom(array('noreply@yoursite.com'))
            ->setTo(array('feedback@yoursite.com'))
            ->setBody($request->get('message'));

        $app['mailer']->send($message);

        return new Response('Thank you for your feedback!', 201);
    });

For more information, check out the `Swift Mailer documentation
<http://swiftmailer.org>`_.
