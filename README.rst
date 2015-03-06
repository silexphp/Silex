Silex, a simple Web Framework
=============================

Silex is a PHP micro-framework to develop websites based on `Symfony2
components`_:

.. code-block:: php

    <?php

    require_once __DIR__.'/../vendor/autoload.php';

    $app = new Silex\Application();

    $app->get('/hello/{name}', function ($name) use ($app) {
      return 'Hello '.$app->escape($name);
    });

    $app->run();

Silex works with PHP 5.3.3 or later.

Installation
------------

The recommended way to install Silex is through `Composer`_:

.. code-block:: bash

    php composer.phar require silex/silex "~1.2"

Alternatively, you can download the `silex.zip`_ file and extract it.

More Information
----------------

Read the `documentation`_ for more information.

Tests
-----

To run the test suite, you need `Composer`_ and `PHPUnit`_:

.. code-block:: bash

    $ composer install
    $ phpunit

Community
---------

Check out #silex-php on irc.freenode.net.

License
-------

Silex is licensed under the MIT license.

.. _Symfony2 components: http://symfony.com
.. _Composer:            http://getcomposer.org
.. _PHPUnit:             https://phpunit.de
.. _silex.zip:           http://silex.sensiolabs.org/download
.. _documentation:       http://silex.sensiolabs.org/documentation
