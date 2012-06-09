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
use Silex\Controller;

/**
 * Builds Silex controllers.
 *
 * It acts as a staging area for routes. You are able to set the route name
 * until flush() is called, at which point all controllers are frozen and
 * converted to a RouteCollection.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerCollection
{
    protected $controllers = array();
    protected $defaultRoute;

    /**
     * Constructor.
     *
     * @param Route $route
     */
    public function __construct()
    {
        $this->defaultRoute = new Route('');
    }

    /**
     * Maps a pattern to a callable.
     *
     * You can optionally specify HTTP methods that should be matched.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Silex\Controller
     */
    public function match($pattern, $to)
    {
        $route = clone $this->defaultRoute;
        $route->setPattern($pattern);
        $route->setDefault('_controller', $to);

        $this->controllers[] = $controller = new Controller($route);

        return $controller;
    }

    /**
     * Maps a GET request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Silex\Controller
     */
    public function get($pattern, $to)
    {
        return $this->match($pattern, $to)->method('GET');
    }

    /**
     * Maps a POST request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Silex\Controller
     */
    public function post($pattern, $to)
    {
        return $this->match($pattern, $to)->method('POST');
    }

    /**
     * Maps a PUT request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Silex\Controller
     */
    public function put($pattern, $to)
    {
        return $this->match($pattern, $to)->method('PUT');
    }

    /**
     * Maps a DELETE request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Silex\Controller
     */
    public function delete($pattern, $to)
    {
        return $this->match($pattern, $to)->method('DELETE');
    }

    /**
     * Sets the requirement for a route variable.
     *
     * @param string $variable The variable name
     * @param string $regexp   The regexp to apply
     *
     * @return Controller $this The current Controller instance
     */
    public function assert($variable, $regexp)
    {
        $this->defaultRoute->assert($variable, $regexp);

        foreach ($this->controllers as $controller) {
            $controller->assert($variable, $regexp);
        }

        return $this;
    }

    /**
     * Sets the default value for a route variable.
     *
     * @param string $variable The variable name
     * @param mixed  $default  The default value
     *
     * @return Controller $this The current Controller instance
     */
    public function value($variable, $default)
    {
        $this->defaultRoute->value($variable, $default);

        foreach ($this->controllers as $controller) {
            $controller->value($variable, $default);
        }

        return $this;
    }

    /**
     * Sets a converter for a route variable.
     *
     * @param string $variable The variable name
     * @param mixed  $callback A PHP callback that converts the original value
     *
     * @return Controller $this The current Controller instance
     */
    public function convert($variable, $callback)
    {
        $this->defaultRoute->convert($variable, $callback);

        foreach ($this->controllers as $controller) {
            $controller->convert($variable, $callback);
        }

        return $this;
    }

    /**
     * Sets the requirement for the HTTP method.
     *
     * @param string $method The HTTP method name. Multiple methods can be supplied, delimited by a pipe character '|', eg. 'GET|POST'
     *
     * @return Controller $this The current Controller instance
     */
    public function method($method)
    {
        $this->defaultRoute->method($method);

        foreach ($this->controllers as $controller) {
            $controller->method($method);
        }

        return $this;
    }

    /**
     * Sets the requirement of HTTP (no HTTPS) on this controller.
     *
     * @return Controller $this The current Controller instance
     */
    public function requireHttp()
    {
        $this->defaultRoute->requireHttp();

        foreach ($this->controllers as $controller) {
            $controller->requireHttp();
        }

        return $this;
    }

    /**
     * Sets the requirement of HTTPS on this controller.
     *
     * @return Controller $this The current Controller instance
     */
    public function requireHttps()
    {
        $this->defaultRoute->requireHttps();

        foreach ($this->controllers as $controller) {
            $controller->requireHttps();
        }

        return $this;
    }

    /**
     * Sets a callback to handle before triggering the route callback.
     * (a.k.a. "Route Middleware")
     *
     * @param mixed $callback A PHP callback to be triggered when the Route is matched, just before the route callback
     *
     * @return Controller $this The current Controller instance
     */
    public function middleware($callback)
    {
        $this->defaultRoute->middleware($callback);

        foreach ($this->controllers as $controller) {
            $controller->middleware($callback);
        }

        return $this;
    }

    /**
     * Persists and freezes staged controllers.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function flush($prefix = '')
    {
        $routes = new RouteCollection();

        foreach ($this->controllers as $controller) {
            if (!$name = $controller->getRouteName()) {
                $name = $controller->generateRouteName($prefix);
                while ($routes->get($name)) {
                    $name .= '_';
                }
                $controller->bind($name);
            }
            $routes->add($name, $controller->getRoute());
            $controller->freeze();
        }

        $this->controllers = array();

        return $routes;
    }
}
