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
use Symfony\Component\Routing\Route;
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
class ControllerCollection extends Controller
{
    protected $controllers = array();

    /**
     * Constructor.
     *
     * @param Route $route
     */
    public function __construct()
    {
        parent::__construct(new Route(''));
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
        $route = clone $this->route;
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
     * {@inheritDoc}
     */
    public function assert($variable, $regexp)
    {
        parent::assert($variable, $regexp);

        foreach ($this->controllers as $controller) {
            $controller->assert($variable, $regexp);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function value($variable, $default)
    {
        parent::value($variable, $default);

        foreach ($this->controllers as $controller) {
            $controller->value($variable, $default);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function convert($variable, $callback)
    {
        parent::convert($variable, $callback);

        foreach ($this->controllers as $controller) {
            $controller->convert($variable, $callback);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function method($method)
    {
        parent::method($method);

        foreach ($this->controllers as $controller) {
            $controller->method($method);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function requireHttp()
    {
        parent::requireHttp();

        foreach ($this->controllers as $controller) {
            $controller->requireHttp();
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function requireHttps()
    {
        parent::requireHttps();

        foreach ($this->controllers as $controller) {
            $controller->requireHttps();
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function middleware($callback)
    {
        parent::middleware($callback);

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
