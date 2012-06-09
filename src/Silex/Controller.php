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

use Silex\Exception\ControllerFrozenException;

/**
 * A wrapper for a controller, mapped to a route.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class Controller
{
    private $route;
    private $routeName;
    private $isFrozen = false;

    /**
     * Constructor.
     *
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * Gets the controller's route.
     *
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Gets the controller's route name.
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Sets the controller's route.
     *
     * @param string $routeName
     *
     * @return Controller $this The current Controller instance
     */
    public function bind($routeName)
    {
        if ($this->isFrozen) {
            throw new ControllerFrozenException(sprintf('Calling %s on frozen %s instance.', __METHOD__, __CLASS__));
        }

        $this->routeName = $routeName;

        return $this;
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
        $this->route->assert($variable, $regexp);

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
        $this->route->value($variable, $default);

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
        $this->route->convert($variable, $callback);

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
        $this->route->method($method);

        return $this;
    }

    /**
     * Sets the requirement of HTTP (no HTTPS) on this controller.
     *
     * @return Controller $this The current Controller instance
     */
    public function requireHttp()
    {
        $this->route->requireHttp();

        return $this;
    }

    /**
     * Sets the requirement of HTTPS on this controller.
     *
     * @return Controller $this The current Controller instance
     */
    public function requireHttps()
    {
        $this->route->requireHttps();

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
        $this->route->middleware($callback);

        return $this;
    }

    /**
     * Freezes the controller.
     *
     * Once the controller is frozen, you can no longer change the route name
     */
    public function freeze()
    {
        $this->isFrozen = true;
    }

    public function generateRouteName($prefix)
    {
        $requirements = $this->route->getRequirements();
        $method = isset($requirements['_method']) ? $requirements['_method'] : '';

        $routeName = $prefix.$method.$this->route->getPattern();
        $routeName = str_replace(array('/', ':', '|', '-'), '_', $routeName);
        $routeName = preg_replace('/[^a-z0-9A-Z_.]+/', '', $routeName);

        return $routeName;
    }
}
