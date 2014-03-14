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
 * @method \Silex\ControllerCollection assert(string $variable, string $regexp)
 * @method \Silex\ControllerCollection value(string $variable, mixed $default)
 * @method \Silex\ControllerCollection convert(string $variable, mixed $callback)
 * @method \Silex\ControllerCollection method(string $method)
 * @method \Silex\ControllerCollection requireHttp()
 * @method \Silex\ControllerCollection requireHttps()
 * @method \Silex\ControllerCollection before(mixed $callback)
 * @method \Silex\ControllerCollection after(mixed $callback)
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerCollection
{
    protected $controllers = array();
    protected $defaultRoute;
    protected $defaultController;
    protected $prefix;

    /**
     * Constructor.
     */
    public function __construct(Route $defaultRoute)
    {
        $this->defaultRoute = $defaultRoute;
        $this->defaultController = function (Request $request) {
            throw new \LogicException(sprintf('The "%s" route must have code to run when it matches.', $request->attributes->get('_route')));
        };
    }

    /**
     * Mounts controllers under the given route prefix.
     *
     * @param string               $prefix      The route prefix
     * @param ControllerCollection $controllers A ControllerCollection instance
     */
    public function mount($prefix, ControllerCollection $controllers)
    {
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

        call_user_func_array(array($this->defaultRoute, $method), $arguments);

        foreach ($this->controllers as $controller) {
            if ($controller instanceof Controller) {
                call_user_func_array(array($controller, $method), $arguments);
            }
        }

        return $this;
    }

    /**
     * Persists and freezes staged controllers.
     *
     * @param string $prefix
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function flush($prefix = '')
    {
        $routes = new RouteCollection();

        foreach ($this->controllers as $controller) {
            if ($controller instanceof Controller) {
                if (!$name = $controller->getRouteName()) {
                    $name = $controller->generateRouteName($prefix);
                    while ($routes->get($name)) {
                        $name .= '_';
                    }
                    $controller->bind($name);
                }
                $routes->add($name, $controller->getRoute());
                $controller->freeze();
            } else {
                $routes->addCollection($controller->flush($controller->prefix));
            }
        }

        $routes->addPrefix($prefix);

        $this->controllers = array();

        return $routes;
    }
}
