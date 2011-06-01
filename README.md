Silex, a simple Web Framework
=============================

Silex is a simple web framework to develop simple websites based on
[Symfony2][1] components:


```php
<?php
require_once __DIR__.'/silex.phar';

$app = new Silex\Application();

$app->get('/hello/{name}', function ($name) {
  return "Hello $name";
});

$app->run();
```

Silex works with PHP 5.3.2 or later.

## Installation

Installing Silex is as easy as it can get. Download the [`silex.phar`][2] file
and you're done!

## More Information

Read the [documentation][3] for more information.

## License

Silex is licensed under the MIT license.

[1]: http://symfony.com
[2]: http://silex-project.org/get/silex.phar
[3]: http://silex-project.org/documentation
