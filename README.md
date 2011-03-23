# Silex, a simple Web Framework

Silex is a simple web framework to develop simple websites:

    require_once __DIR__.'/silex.phar';

    use Silex\Application;

    $app = new Application();

    $app->get('/hello/{name}', function($name) {
        return "Hello $name";
    });

    $app->match('/goodbye/{name}', function($name) {
        return "Goodbye $name";
    });

    $app->error(function($e) {
        return "An error occured<br />" . $e->getMessage();
    });

    $app->run();

Silex is based on [Symfony2][1].

## Requirements

Silex works with PHP 5.3.2 or later.

## Installation

Installing Silex is as easy as it can get. Build your **silex.php** (see "Build" section)  and you're done! 

## Test Suite

You can run the [PHPUnit][2] test suite by running `phpunit`.

## Build

You can build the silex.phar file by running `php compile.php`.

## More Information

Read the documentation of Symfony2 for more information about how you can
leverage Symfony2 features.

## Slides

 - [Silex - The Symfony2 Microframework][3]

## License

Silex is licensed under the MIT license.

[1]: http://symfony.com
[2]: https://github.com/sebastianbergmann/phpunit
[3]: http://www.slideshare.net/IgorWiedler/silex-the-symfony2-microframework
