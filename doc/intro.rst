Introduction
============

Silex is a PHP microframework for PHP 5.3. It is built on the shoulders of
`Symfony2`_ and `Pimple`_ and also inspired by `Sinatra`_.

A microframework provides the guts for building simple single-file apps. Silex
aims to be:

* *Concise*: Silex exposes an intuitive and concise API that is fun to use.

* *Extensible*: Silex has an extension system based around the Pimple micro
  service-container that makes it even easier to tie in third party libraries.

* *Testable*: Silex uses Symfony2's HttpKernel which abstracts request and
  response. This makes it very easy to test apps and the framework itself. It
  also respects the HTTP specification and encourages its proper use.

In a nutshell, you define controllers and map them to routes, all in one step.

Usage
-----

.. code-block:: php

    // web/index.php
    require_once __DIR__.'/../vendor/autoload.php';

    $app = new Silex\Application();

    $app->get('/hello/{name}', function ($name) use ($app) {
        return 'Hello '.$app->escape($name);
    });

    $app->run();

All that is needed to get access to the Framework is to include the
autoloader.

Next a route for ``/hello/{name}`` that matches for ``GET`` requests is defined.
When the route matches, the function is executed and the return value is sent
back to the client.

Finally, the app is run. Visit ``/hello/world`` to see the result. It's really
that easy!

Installation
------------

Installing Silex is as easy as it can get. The recommend method is using
Composer_ and requiring `silex/silex`_.  Another way is to `download`_ the
archive file and extract it.

.. _Symfony2: http://symfony.com/
.. _Pimple: http://pimple.sensiolabs.org/
.. _Sinatra: http://www.sinatrarb.com/
.. _Composer: http://getcomposer.org/
.. _`download`: http://silex.sensiolabs.org/download
.. _`silex/silex`: https://packagist.org/packages/silex/silex
