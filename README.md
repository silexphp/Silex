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

The same effect can be achieved by an route file an controller classes. Create
at controller:

    // src/MyProject/Controller/MyController.php

    namespace MyProject\Controller;

    class MyController
    {
        public function index()
        {
            return 'Index';
        }

        public function hello($name)
        {
            return 'Hello ' . $name;
        }
    }

Create a routing file:

    # config/routing.yml

    _home:
        pattern: /
        defaults: { _controller: MyProject\Controller\MyController::index }

    _hello:
        pattern: /hello/{name}
        defaults: { _controller: MyProject\Controller\MyController::hello }

Add the namespace to autoloading:

    // autoload.php

    $loader->registerNamespaces(array(
        // other namespaces
        'MyProject' => __DIR__.'/src',
    ));

Now your website looks like this:

    require_once __DIR__.'/silex.phar';

    use Silex\Application;

    $app = new Application();
    $app->loadRoutes(__DIR__.'/config/routing.yml');
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

## Slides

 - [Silex - The Symfony2 Microframework][4]

## License

Silex is licensed under the MIT license.

[1]: http://symfony.com
[2]: http://github.com/fabpot/silex/blob/master/silex.phar
[3]: https://github.com/sebastianbergmann/phpunit
[4]: http://www.slideshare.net/IgorWiedler/silex-the-symfony2-microframework
