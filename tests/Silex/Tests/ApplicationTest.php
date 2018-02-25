<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests;

use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\Api\ControllerProviderInterface;
use Silex\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * Application test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ApplicationTest extends TestCase
{
    public function testMatchReturnValue()
    {
        $app = new Application();

        $returnValue = $app->match('/foo', function () {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->get('/foo', function () {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->post('/foo', function () {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->put('/foo', function () {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->patch('/foo', function () {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);

        $returnValue = $app->delete('/foo', function () {});
        $this->assertInstanceOf('Silex\Controller', $returnValue);
    }

    public function testConstructorInjection()
    {
        // inject a custom parameter
        $params = ['param' => 'value'];
        $app = new Application($params);
        $this->assertSame($params['param'], $app['param']);

        // inject an existing parameter
        $params = ['locale' => 'value'];
        $app = new Application($params);
        $this->assertSame($params['locale'], $app['locale']);
    }

    public function testGetRequest()
    {
        $request = Request::create('/');

        $app = new Application();
        $app->get('/', function (Request $req) use ($request) {
            return $request === $req ? 'ok' : 'ko';
        });

        $this->assertEquals('ok', $app->handle($request)->getContent());
    }

    public function testGetRoutesWithNoRoutes()
    {
        $app = new Application();

        $routes = $app['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertCount(0, $routes->all());
    }

    public function testGetRoutesWithRoutes()
    {
        $app = new Application();

        $app->get('/foo', function () {
            return 'foo';
        });

        $app->get('/bar')->run(function () {
            return 'bar';
        });

        $routes = $app['routes'];
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertCount(0, $routes->all());
        $app->flush();
        $this->assertCount(2, $routes->all());
    }

    public function testOnCoreController()
    {
        $app = new Application();

        $app->get('/foo/{foo}', function (\ArrayObject $foo) {
            return $foo['foo'];
        })->convert('foo', function ($foo) { return new \ArrayObject(['foo' => $foo]); });

        $response = $app->handle(Request::create('/foo/bar'));
        $this->assertEquals('bar', $response->getContent());

        $app->get('/foo/{foo}/{bar}', function (\ArrayObject $foo) {
            return $foo['foo'];
        })->convert('foo', function ($foo, Request $request) { return new \ArrayObject(['foo' => $foo.$request->attributes->get('bar')]); });

        $response = $app->handle(Request::create('/foo/foo/bar'));
        $this->assertEquals('foobar', $response->getContent());
    }

    public function testOn()
    {
        $app = new Application();
        $app['pass'] = false;

        $app->on('test', function (Event $e) use ($app) {
            $app['pass'] = true;
        });

        $app['dispatcher']->dispatch('test');

        $this->assertTrue($app['pass']);
    }

    public function testAbort()
    {
        $app = new Application();

        try {
            $app->abort(404);
            $this->fail();
        } catch (HttpException $e) {
            $this->assertEquals(404, $e->getStatusCode());
        }
    }

    /**
     * @dataProvider escapeProvider
     */
    public function testEscape($expected, $text)
    {
        $app = new Application();

        $this->assertEquals($expected, $app->escape($text));
    }

    public function escapeProvider()
    {
        return [
            ['&lt;', '<'],
            ['&gt;', '>'],
            ['&quot;', '"'],
            ["'", "'"],
            ['abc', 'abc'],
        ];
    }

    public function testControllersAsMethods()
    {
        $app = new Application();
        unset($app['exception_handler']);

        $app->get('/{name}', 'Silex\Tests\FooController::barAction');

        $this->assertEquals('Hello Fabien', $app->handle(Request::create('/Fabien'))->getContent());
    }

    public function testApplicationTypeHintWorks()
    {
        $app = new SpecialApplication();
        unset($app['exception_handler']);

        $app->get('/{name}', 'Silex\Tests\FooController::barSpecialAction');

        $this->assertEquals('Hello Fabien in Silex\Tests\SpecialApplication', $app->handle(Request::create('/Fabien'))->getContent());
    }

    /**
     * @requires PHP 7.0
     */
    public function testPhp7TypeHintWorks()
    {
        $app = new SpecialApplication();
        unset($app['exception_handler']);

        $app->get('/{name}', 'Silex\Tests\Fixtures\Php7Controller::typehintedAction');

        $this->assertEquals('Hello Fabien in Silex\Tests\SpecialApplication', $app->handle(Request::create('/Fabien'))->getContent());
    }

    public function testHttpSpec()
    {
        $app = new Application();
        $app['charset'] = 'ISO-8859-1';

        $app->get('/', function () {
            return 'hello';
        });

        // content is empty for HEAD requests
        $response = $app->handle(Request::create('/', 'HEAD'));
        $this->assertEquals('', $response->getContent());

        // charset is appended to Content-Type
        $response = $app->handle(Request::create('/'));

        $this->assertEquals('text/html; charset=ISO-8859-1', $response->headers->get('Content-Type'));
    }

    public function testRoutesMiddlewares()
    {
        $app = new Application();

        $test = $this;

        $middlewareTarget = [];
        $beforeMiddleware1 = function (Request $request) use (&$middlewareTarget, $test) {
            $test->assertEquals('/reached', $request->getRequestUri());
            $middlewareTarget[] = 'before_middleware1_triggered';
        };
        $beforeMiddleware2 = function (Request $request) use (&$middlewareTarget, $test) {
            $test->assertEquals('/reached', $request->getRequestUri());
            $middlewareTarget[] = 'before_middleware2_triggered';
        };
        $beforeMiddleware3 = function (Request $request) use (&$middlewareTarget, $test) {
            throw new \Exception('This middleware shouldn\'t run!');
        };

        $afterMiddleware1 = function (Request $request, Response $response) use (&$middlewareTarget, $test) {
            $test->assertEquals('/reached', $request->getRequestUri());
            $middlewareTarget[] = 'after_middleware1_triggered';
        };
        $afterMiddleware2 = function (Request $request, Response $response) use (&$middlewareTarget, $test) {
            $test->assertEquals('/reached', $request->getRequestUri());
            $middlewareTarget[] = 'after_middleware2_triggered';
        };
        $afterMiddleware3 = function (Request $request, Response $response) use (&$middlewareTarget, $test) {
            throw new \Exception('This middleware shouldn\'t run!');
        };

        $app->get('/reached', function () use (&$middlewareTarget) {
            $middlewareTarget[] = 'route_triggered';

            return 'hello';
        })
        ->before($beforeMiddleware1)
        ->before($beforeMiddleware2)
        ->after($afterMiddleware1)
        ->after($afterMiddleware2);

        $app->get('/never-reached', function () use (&$middlewareTarget) {
            throw new \Exception('This route shouldn\'t run!');
        })
        ->before($beforeMiddleware3)
        ->after($afterMiddleware3);

        $result = $app->handle(Request::create('/reached'));

        $this->assertSame(['before_middleware1_triggered', 'before_middleware2_triggered', 'route_triggered', 'after_middleware1_triggered', 'after_middleware2_triggered'], $middlewareTarget);
        $this->assertEquals('hello', $result->getContent());
    }

    public function testRoutesBeforeMiddlewaresWithResponseObject()
    {
        $app = new Application();

        $app->get('/foo', function () {
            throw new \Exception('This route shouldn\'t run!');
        })
        ->before(function () {
            return new Response('foo');
        });

        $request = Request::create('/foo');
        $result = $app->handle($request);

        $this->assertEquals('foo', $result->getContent());
    }

    public function testRoutesAfterMiddlewaresWithResponseObject()
    {
        $app = new Application();

        $app->get('/foo', function () {
            return new Response('foo');
        })
        ->after(function () {
            return new Response('bar');
        });

        $request = Request::create('/foo');
        $result = $app->handle($request);

        $this->assertEquals('bar', $result->getContent());
    }

    public function testRoutesBeforeMiddlewaresWithRedirectResponseObject()
    {
        $app = new Application();

        $app->get('/foo', function () {
            throw new \Exception('This route shouldn\'t run!');
        })
        ->before(function () use ($app) {
            return $app->redirect('/bar');
        });

        $request = Request::create('/foo');
        $result = $app->handle($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertEquals('/bar', $result->getTargetUrl());
    }

    public function testRoutesBeforeMiddlewaresTriggeredAfterSilexBeforeFilters()
    {
        $app = new Application();

        $middlewareTarget = [];
        $middleware = function (Request $request) use (&$middlewareTarget) {
            $middlewareTarget[] = 'middleware_triggered';
        };

        $app->get('/foo', function () use (&$middlewareTarget) {
            $middlewareTarget[] = 'route_triggered';
        })
        ->before($middleware);

        $app->before(function () use (&$middlewareTarget) {
            $middlewareTarget[] = 'before_triggered';
        });

        $app->handle(Request::create('/foo'));

        $this->assertSame(['before_triggered', 'middleware_triggered', 'route_triggered'], $middlewareTarget);
    }

    public function testRoutesAfterMiddlewaresTriggeredBeforeSilexAfterFilters()
    {
        $app = new Application();

        $middlewareTarget = [];
        $middleware = function (Request $request) use (&$middlewareTarget) {
            $middlewareTarget[] = 'middleware_triggered';
        };

        $app->get('/foo', function () use (&$middlewareTarget) {
            $middlewareTarget[] = 'route_triggered';
        })
        ->after($middleware);

        $app->after(function () use (&$middlewareTarget) {
            $middlewareTarget[] = 'after_triggered';
        });

        $app->handle(Request::create('/foo'));

        $this->assertSame(['route_triggered', 'middleware_triggered', 'after_triggered'], $middlewareTarget);
    }

    public function testFinishFilter()
    {
        $containerTarget = [];

        $app = new Application();

        $app->finish(function () use (&$containerTarget) {
            $containerTarget[] = '4_filterFinish';
        });

        $app->get('/foo', function () use (&$containerTarget) {
            $containerTarget[] = '1_routeTriggered';

            return new StreamedResponse(function () use (&$containerTarget) {
                $containerTarget[] = '3_responseSent';
            });
        });

        $app->after(function () use (&$containerTarget) {
            $containerTarget[] = '2_filterAfter';
        });

        $app->run(Request::create('/foo'));

        $this->assertSame(['1_routeTriggered', '2_filterAfter', '3_responseSent', '4_filterFinish'], $containerTarget);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNonResponseAndNonNullReturnFromRouteBeforeMiddlewareShouldThrowRuntimeException()
    {
        $app = new Application();

        $middleware = function (Request $request) {
            return 'string return';
        };

        $app->get('/', function () {
            return 'hello';
        })
        ->before($middleware);

        $app->handle(Request::create('/'), HttpKernelInterface::MASTER_REQUEST, false);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNonResponseAndNonNullReturnFromRouteAfterMiddlewareShouldThrowRuntimeException()
    {
        $app = new Application();

        $middleware = function (Request $request) {
            return 'string return';
        };

        $app->get('/', function () {
            return 'hello';
        })
        ->after($middleware);

        $app->handle(Request::create('/'), HttpKernelInterface::MASTER_REQUEST, false);
    }

    public function testSubRequest()
    {
        $app = new Application();
        $app->get('/sub', function (Request $request) {
            return new Response('foo');
        });
        $app->get('/', function (Request $request) use ($app) {
            return $app->handle(Request::create('/sub'), HttpKernelInterface::SUB_REQUEST);
        });

        $this->assertEquals('foo', $app->handle(Request::create('/'))->getContent());
    }

    public function testRegisterShouldReturnSelf()
    {
        $app = new Application();
        $provider = $this->getMockBuilder('Pimple\ServiceProviderInterface')->getMock();

        $this->assertSame($app, $app->register($provider));
    }

    public function testMountShouldReturnSelf()
    {
        $app = new Application();
        $mounted = new ControllerCollection(new Route());
        $mounted->get('/{name}', function ($name) { return new Response($name); });

        $this->assertSame($app, $app->mount('/hello', $mounted));
    }

    public function testMountPreservesOrder()
    {
        $app = new Application();
        $mounted = new ControllerCollection(new Route());
        $mounted->get('/mounted')->bind('second');

        $app->get('/before')->bind('first');
        $app->mount('/', $mounted);
        $app->get('/after')->bind('third');
        $app->flush();

        $this->assertEquals(['first', 'second', 'third'], array_keys(iterator_to_array($app['routes'])));
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage The "mount" method takes either a "ControllerCollection" instance, "ControllerProviderInterface" instance, or a callable.
     */
    public function testMountNullException()
    {
        $app = new Application();
        $app->mount('/exception', null);
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage The method "Silex\Tests\IncorrectControllerCollection::connect" must return a "ControllerCollection" instance. Got: "NULL"
     */
    public function testMountWrongConnectReturnValueException()
    {
        $app = new Application();
        $app->mount('/exception', new IncorrectControllerCollection());
    }

    public function testMountCallable()
    {
        $app = new Application();
        $app->mount('/prefix', function (ControllerCollection $coll) {
            $coll->get('/path');
        });

        $app->flush();

        $this->assertEquals(1, $app['routes']->count());
    }

    public function testSendFile()
    {
        $app = new Application();

        $response = $app->sendFile(__FILE__, 200, ['Content-Type: application/php']);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse', $response);
        $this->assertEquals(__FILE__, (string) $response->getFile());
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage The "homepage" route must have code to run when it matches.
     */
    public function testGetRouteCollectionWithRouteWithoutController()
    {
        $app = new Application();
        unset($app['exception_handler']);
        $app->match('/')->bind('homepage');
        $app->handle(Request::create('/'));
    }

    public function testBeforeFilterOnMountedControllerGroupIsolatedToGroup()
    {
        $app = new Application();
        $app->match('/', function () { return new Response('ok'); });
        $mounted = $app['controllers_factory'];
        $mounted->before(function () { return new Response('not ok'); });
        $app->mount('/group', $mounted);

        $response = $app->handle(Request::create('/'));
        $this->assertEquals('ok', $response->getContent());
    }

    public function testViewListenerWithPrimitive()
    {
        $app = new Application();
        $app->get('/foo', function () { return 123; });
        $app->view(function ($view, Request $request) {
            return new Response($view);
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertEquals('123', $response->getContent());
    }

    public function testViewListenerWithArrayTypeHint()
    {
        $app = new Application();
        $app->get('/foo', function () { return ['ok']; });
        $app->view(function (array $view) {
            return new Response($view[0]);
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertEquals('ok', $response->getContent());
    }

    public function testViewListenerWithObjectTypeHint()
    {
        $app = new Application();
        $app->get('/foo', function () { return (object) ['name' => 'world']; });
        $app->view(function (\stdClass $view) {
            return new Response('Hello '.$view->name);
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertEquals('Hello world', $response->getContent());
    }

    public function testViewListenerWithCallableTypeHint()
    {
        $app = new Application();
        $app->get('/foo', function () { return function () { return 'world'; }; });
        $app->view(function (callable $view) {
            return new Response('Hello '.$view());
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertEquals('Hello world', $response->getContent());
    }

    public function testViewListenersCanBeChained()
    {
        $app = new Application();
        $app->get('/foo', function () { return (object) ['name' => 'world']; });

        $app->view(function (\stdClass $view) {
            return ['msg' => 'Hello '.$view->name];
        });

        $app->view(function (array $view) {
            return $view['msg'];
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertEquals('Hello world', $response->getContent());
    }

    public function testViewListenersAreIgnoredIfNotSuitable()
    {
        $app = new Application();
        $app->get('/foo', function () { return 'Hello world'; });

        $app->view(function (\stdClass $view) {
            throw new \Exception('View listener was called');
        });

        $app->view(function (array $view) {
            throw new \Exception('View listener was called');
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertEquals('Hello world', $response->getContent());
    }

    public function testViewListenersResponsesAreNotUsedIfNull()
    {
        $app = new Application();
        $app->get('/foo', function () { return 'Hello world'; });

        $app->view(function ($view) {
            return 'Hello view listener';
        });

        $app->view(function ($view) {
            return;
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertEquals('Hello view listener', $response->getContent());
    }

    public function testWebLinkListener()
    {
        if (!class_exists(HttpHeaderSerializer::class)) {
            self::markTestSkipped('Symfony WebLink component is required.');
        }

        $app = new Application();

        $app->get('/', function () {
            return 'hello';
        });

        $request = Request::create('/');
        $request->attributes->set('_links', (new GenericLinkProvider())->withLink(new Link('preload', '/foo.css')));

        $response = $app->handle($request);

        $this->assertEquals('</foo.css>; rel="preload"', $response->headers->get('Link'));
    }

    public function testDefaultRoutesFactory()
    {
        $app = new Application();
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $app['routes']);
    }

    public function testOverriddenRoutesFactory()
    {
        $app = new Application();
        $app['routes_factory'] = $app->factory(function () {
            return new RouteCollectionSubClass();
        });
        $this->assertInstanceOf('Silex\Tests\RouteCollectionSubClass', $app['routes']);
    }
}

class FooController
{
    public function barAction(Application $app, $name)
    {
        return 'Hello '.$app->escape($name);
    }

    public function barSpecialAction(SpecialApplication $app, $name)
    {
        return 'Hello '.$app->escape($name).' in '.get_class($app);
    }
}

class IncorrectControllerCollection implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        return;
    }
}

class RouteCollectionSubClass extends RouteCollection
{
}

class SpecialApplication extends Application
{
}
