ErrorExtension
==============

The *ErrorExtension* provides a default error handler.

It catches all exceptions and convert them to Responses, depending on the
value of the **debug** parameter. When **debug** is true, it displays error
messages with stack trace; if not, it displays a simple message to the end
user.

.. note::

    This error handler can easily be overwritten by calling the ``error()``
    method on the application.

Parameters
----------

None.

Services
--------

None.

Registering
-----------

::

    $app->register(new Silex\Extension\ErrorExtension());
