# Silex, a simple Web Framework

Silex is a simple web framework to develop simple websites:

    require_once __DIR__.'/silex.phar';

    $app = new Silex\Application();

    $app->get('/hello/{name}', function($name) {
        return "Hello $name";
    });

    $app->match('/goodbye/{name}', function($name) {
        return "Goodbye $name";
    });

    $app->error(function($e) {
        return "An error occured<br />".$e->getMessage();
    });

    $app->run();

Silex is based on [Symfony2][1].

## Requirements

Silex works with PHP 5.3.2 or later.

## Installation

Installing Silex is as easy as it can get. Download the [`silex.phar`][2] file
and you're done!

## Test Suite

You can run the [PHPUnit][3] test suite by running `phpunit`.

## Build

You can build the `silex.phar` file by running `php compile`.

## More Information

Read the [documentation][5] for more information.

## Slides

 - [Silex - The Symfony2 Microframework][4]

## License

Silex is licensed under the MIT license.

[1]: http://symfony.com
[2]: http://silex-project.org/get/silex.phar
[3]: https://github.com/sebastianbergmann/phpunit
[4]: http://www.slideshare.net/IgorWiedler/silex-the-symfony2-microframework
[5]: http://silex-project.org/documentation
