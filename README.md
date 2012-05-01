Silex, a simple Web Framework
=============================

[![Build Status](https://secure.travis-ci.org/fabpot/Silex.png?branch=master)](http://travis-ci.org/fabpot/Silex)

Silex is a simple web framework to develop simple websites based on
[Symfony2][1] components:


```php
<?php
require_once __DIR__.'/silex.phar';

$app = new Silex\Application();

$app->get('/hello/{name}', function ($name) use ($app) {
  return 'Hello '.$app->escape($name);
});

$app->run();
```

Silex works with PHP 5.3.2 or later.

## Installation

Installing Silex is as easy as it can get. Download the [`silex.phar`][2] file
and you're done!

## More Information

Read the [documentation][3] for more information.

## Tests

To run the test suite, you need [composer](http://getcomposer.org) and
[PHPUnit](https://github.com/sebastianbergmann/phpunit).

    $ php composer.phar install --dev
    $ phpunit

## License

Silex is licensed under the MIT license.

[1]: http://symfony.com
[2]: http://silex.sensiolabs.org/get/silex.phar
[3]: http://silex.sensiolabs.org/documentation
