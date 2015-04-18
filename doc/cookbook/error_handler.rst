Converting Errors to Exceptions
===============================

Silex catches exceptions that are thrown from within a request/response cycle.
However, it does *not* catch PHP errors and notices. This recipe tells you how
to catch them by converting them to exceptions.

Registering the ErrorHandler
----------------------------

The ``Symfony/Debug`` package has an ``ErrorHandler`` class that solves this
problem. It converts all errors to exceptions, and exceptions are then caught
by Silex.

Register it by calling the static ``register`` method::

    use Symfony\Component\Debug\ErrorHandler;

    ErrorHandler::register();

It is recommended that you do this as early as possible.

Handling fatal errors
---------------------

To handle fatal errors, you can additionally register a global
``ExceptionHandler``::

    use Symfony\Component\Debug\ExceptionHandler;

    ExceptionHandler::register();

In production you may want to disable the debug output by passing ``false`` as
the ``$debug`` argument::

    use Symfony\Component\Debug\ExceptionHandler;

    ExceptionHandler::register(false);
