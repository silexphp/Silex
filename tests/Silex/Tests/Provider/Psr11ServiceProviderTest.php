<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Pimple\Psr11\Container;
use Psr\Container\ContainerInterface;
use Silex\Application;
use Silex\Provider\Psr11ServiceProvider;
use Silex\Tests\Fixtures\Psr11\ChildContainer;
use Silex\Tests\Fixtures\Psr11\ChildInterface;
use Silex\Tests\Fixtures\Psr11\ParentContainer;
use Symfony\Component\HttpFoundation\Request;

class Psr11ServiceProviderTest extends TestCase
{
    public function testCanAccessSilexServices()
    {
        $app = new Application();
        $app->register(new Psr11ServiceProvider());

        $app['service'] = function () {
            return new \stdClass();
        };

        $this->assertSame($app['service'], $app['psr11']->get('service'));
    }

    /**
     * @dataProvider provideControllers
     */
    public function testResolvableArgument($controller, $containerFactory)
    {
        $app = new Application();
        $app->register(new Psr11ServiceProvider());

        if (null !== $containerFactory) {
            $app['psr11'] = $containerFactory;
        }
        $app->get('/', $controller);

        $this->assertSame('ok', $app->handle(Request::create('/'))->getContent());
    }

    public function provideControllers()
    {
        return array(
            // ContainerInterface
            array(
                function (Application $app, ContainerInterface $container) { return $container === $app['psr11'] ? 'ok' : 'ko'; },
                null,
            ),
            // Exact class of the container
            array(
                function (Application $app, Container $container) { return $container === $app['psr11'] ? 'ok' : 'ko'; },
                null,
            ),
            // Child interface implemented by the container
            array(
                function (Application $app, ChildInterface $container) { return $container === $app['psr11'] ? 'ok' : 'ko'; },
                function () { return new ChildContainer(); },
            ),
            // Parent class
            array(
                function (Application $app, ParentContainer $container) { return $container === $app['psr11'] ? 'ok' : 'ko'; },
                function () { return new ChildContainer(); },
            ),
        );
    }

    /**
     * @dataProvider provideBadControllers
     */
    public function testUnresolvableArgument($controller, $containerFactory)
    {
        $app = new Application();
        $app->register(new Psr11ServiceProvider());

        if (null !== $containerFactory) {
            $app['psr11'] = $containerFactory;
        }
        $app->get('/', $controller);

        $this->assertSame('ko', $app->handle(Request::create('/'))->getContent());
    }

    public function provideBadControllers()
    {
        return array(
            // Unrelated class
            array(
                function (Application $app, ParentContainer $container = null) { return $container === $app['psr11'] ? 'ok' : 'ko'; },
                null,
            ),
            // Child interface not implemented by the container
            array(
                function (Application $app, ChildInterface $container = null) { return $container === $app['psr11'] ? 'ok' : 'ko'; },
                null,
            ),
        );
    }

    public function testResolvedArgumentIsNotOverridden()
    {
        $app = new Application();
        $app->register(new Psr11ServiceProvider());

        $app->get('/', function (Application $app, ContainerInterface $container) {
            return $container === $app['psr11'] ? 'ok' : 'ko';
        })->convert('container', function () {
            return new ParentContainer();
        });

        $this->assertSame('ko', $app->handle(Request::create('/'))->getContent());
    }
}
