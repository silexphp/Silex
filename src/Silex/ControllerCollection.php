<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds Silex controllers.
 *
 * It acts as a staging area for routes. You are able to set the route name
 * until flush() is called, at which point all controllers are frozen and
 * converted to a RouteCollection.
 *
 * __call() forwards method-calls to Route, but returns instance of ControllerCollection
 * listing Route's methods below, so that IDEs know they are valid
 *
 * @method ControllerCollection assert(string $variable, string $regexp)
 * @method ControllerCollection value(string $variable, mixed $default)
 * @method ControllerCollection convert(string $variable, mixed $callback)
 * @method ControllerCollection method(string $method)
 * @method ControllerCollection requireHttp()
 * @method ControllerCollection requireHttps()
 * @method ControllerCollection before(mixed $callback)
 * @method ControllerCollection after(mixed $callback)
 * @method ControllerCollection when(string $condition)
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerCollection
{
    protected $controllers = [];
    protected $defaultRoute;
    protected $defaultController;
    protected $prefix;
    protected $routesFactory;
    protected $controllersFactory;

    public function __construct(Route $defaultRoute, RouteCollection $routesFactory = null, $controllersFactory = null)
    {
        $this->defaultRoute = $defaultRoute;
        $this->routesFactory = $routesFactory;
        $this->controllersFactory = $controllersFactory;
        $this->defaultController = function (Request $request) {
            throw new \LogicException(sprintf('The "%s" route must have code to run when it matches.', $request->attributes->get('_route')));
        };
    }

    /**
     * Mounts controllers under the given route prefix.
     *
     * @param string                        $prefix      The route prefix
     * @param ControllerCollection|callable $controllers A ControllerCollection instance or a callable for defining routes
     *
     * @throws \LogicException
     */
    public function mount($prefix, $controllers)
    {
        if (is_callable($controllers)) {
            $collection = $this->controllersFactory ? call_user_func($this->controllersFactory) : new static(new Route(), new RouteCollection());
            $collection->defaultRoute = clone $this->defaultRoute;
            call_user_func($controllers, $collection);
            $controllers = $collection;
        } elseif (!$controllers instanceof self) {
            throw new \LogicException('The "mount" method takes either a "ControllerCollection" instance or callable.');
        }

        $controllers->prefix = $prefix;

        $this->controllers[] = $controllers;
    }

    /**
     * Maps a pattern to a callable.
     *
     * You can optionally specify HTTP methods that should be matched.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function match($pattern, $to = null)
    {
        $route = clone $this->defaultRoute;
        $route->setPath($pattern);
        $this->controllers[] = $controller = new Controller($route);
        $route->setDefault('_controller', null === $to ? $this->defaultController : $to);

        return $controller;
    }

    /**
     * Maps a GET request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function get($pattern, $to = null)
    {
        return $this->match($pattern, $to)->method('GET');
    }

    /**
     * Maps a POST request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function post($pattern, $to = null)
    {
        return $this->match($pattern, $to)->method('POST');
    }

    /**
     * Maps a PUT request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function put($pattern, $to = null)
    {
        return $this->match($pattern, $to)->method('PUT');
    }

    /**
     * Maps a DELETE request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function delete($pattern, $to = null)
    {
        return $this->match($pattern, $to)->method('DELETE');
    }

    /**
     * Maps an OPTIONS request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function options($pattern, $to = null)
    {
        return $this->match($pattern, $to)->method('OPTIONS');
    }

    /**
     * Maps a PATCH request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function patch($pattern, $to = null)
    {
        return $this->match($pattern, $to)->method('PATCH');
    }

    public function __call($method, $arguments)
    {
        if (!method_exists($this->defaultRoute, $method)) {
            throw new \BadMethodCallException(sprintf('Method "%s::%s" does not exist.', get_class($this->defaultRoute), $method));
        }

        call_user_func_array([$this->defaultRoute, $method], $arguments);

        foreach ($this->controllers as $controller) {
            call_user_func_array([$controller, $method], $arguments);
        }

        return $this;
    }

    /**
     * Persists and freezes staged controllers.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function flush()
    {
        if (null === $this->routesFactory) {
            $routes = new RouteCollection();
        } else {
            $routes = $this->routesFactory;
        }

        return $this->doFlush('', $routes);
    }

    private function doFlush($prefix, RouteCollection $routes)
    {
        if ('' !== $prefix) {
            $prefix = '/'.trim(trim($prefix), '/');
        }

        foreach ($this->controllers as $controller) {
            if ($controller instanceof Controller) {
                $controller->getRoute()->setPath($prefix.$controller->getRoute()->getPath());
                if (!$name = $controller->getRouteName()) {
                    $name = $base = $controller->generateRouteName('');
                    $i = 0;
                    while ($routes->get($name)) {
                        $name = $base.'_'.++$i;
                    }
                    $controller->bind($name);
                }
                $routes->add($name, $controller->getRoute());
                $controller->freeze();
            } else {
                $controller->doFlush($prefix.$controller->prefix, $routes);
            }
        }

        $this->controllers = [];

        return $routes;
    }
}
