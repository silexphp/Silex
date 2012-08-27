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

use Symfony\Component\Routing\Route as BaseRoute;

/**
 * A wrapper for a controller, mapped to a route.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Route extends BaseRoute
{
    public function __construct($pattern = '', array $defaults = array(), array $requirements = array(), array $options = array())
    {
        parent::__construct($pattern, $defaults, $requirements, $options);
    }

    /**
     * Sets the requirement for a route variable.
     *
     * @param string $variable The variable name
     * @param string $regexp   The regexp to apply
     *
     * @return Route $this The current route instance
     */
    public function assert($variable, $regexp)
    {
        return $this->setRequirement($variable, $regexp);
    }

    /**
     * Sets the default value for a route variable.
     *
     * @param string $variable The variable name
     * @param mixed  $default  The default value
     *
     * @return Route $this The current Route instance
     */
    public function value($variable, $default)
    {
        return $this->setDefault($variable, $default);
    }

    /**
     * Sets a converter for a route variable.
     *
     * @param string $variable The variable name
     * @param mixed  $callback A PHP callback that converts the original value
     *
     * @return Route $this The current Route instance
     */
    public function convert($variable, $callback)
    {
        $converters = $this->getOption('_converters');
        $converters[$variable] = $callback;

        return $this->setOption('_converters', $converters);
    }

    /**
     * Sets the requirement for the HTTP method.
     *
     * @param string $method The HTTP method name. Multiple methods can be supplied, delimited by a pipe character '|', eg. 'GET|POST'
     *
     * @return Route $this The current Route instance
     */
    public function method($method)
    {
        return $this->setRequirement('_method', $method);
    }

    /**
     * Sets the requirement of HTTP (no HTTPS) on this Route.
     *
     * @return Route $this The current Route instance
     */
    public function requireHttp()
    {
        return $this->setRequirement('_scheme', 'http');
    }

    /**
     * Sets the requirement of HTTPS on this Route.
     *
     * @return Route $this The current Route instance
     */
    public function requireHttps()
    {
        return $this->setRequirement('_scheme', 'https');
    }

    /**
     * Sets a callback to handle before triggering the route callback.
     *
     * @param mixed $callback A PHP callback to be triggered when the Route is matched, just before the route callback
     *
     * @return Route $this The current Route instance
     */
    public function before($callback)
    {
        $callbacks = $this->getOption('_before_middlewares');
        $callbacks[] = $callback;

        return $this->setOption('_before_middlewares', $callbacks);
    }

    /**
     * Sets a callback to handle after the route callback.
     *
     * @param mixed $callback A PHP callback to be triggered after the route callback
     *
     * @return Route $this The current Route instance
     */
    public function after($callback)
    {
        $callbacks = $this->getOption('_after_middlewares');
        $callbacks[] = $callback;

        return $this->setOption('_after_middlewares', $callbacks);
    }
}
