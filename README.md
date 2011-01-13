# Silex, a simple Web Framework

Silex is a simple web framework to develop simple websites:

    require_once __DIR__.'/silex.phar';

    use Silex\Framework;

    $app = Framework::create();

    $app->get('/home/{name}', function($name) {
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

Installing Silex is as easy as it can get. Download the [`silex.phar`][2] file
and you're done!

## Test Suite

You can run the [PHPUnit][3] test suite by running `phpunit`.

## Build

You can build the silex.phar file by running `php compile.php`.

## More Information

Read the documentation of Symfony2 for more information about how you can
leverage Symfony2 features.

## License

Silex is licensed under the MIT license.

[1]: http://symfony-reloaded.org/
[2]: http://github.com/fabpot/silex/blob/master/silex.phar
[3]: https://github.com/sebastianbergmann/phpunit
