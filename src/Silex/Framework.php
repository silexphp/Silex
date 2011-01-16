<?php

namespace Silex;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * The Silex framework class.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class Framework extends HttpKernel
{
    protected $routes;
    protected $handlers = array('error' => array(), 'before' => array(), 'after' => array());

    /**
     * Constructor.
     *
     * Takes a route map argument (assoc array). The key is (optional) a pipe '|'
     * delimited list of HTTP methods followed by a single space ' ', followed by
     * the path pattern to match. The value is a callable.
     *
     * @param array $map Route map, mapping patterns to callables
     */
    public function __construct(array $map = null)
    {
        $this->routes = new RouteCollection();

        if ($map) {
            $this->parseRouteMap($map);
        }

        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.request', array($this, 'parseRequest'));
        $dispatcher->connect('core.request', array($this, 'runBeforeFilters'));
        $dispatcher->connect('core.view', array($this, 'parseResponse'));
        $dispatcher->connect('core.view', array($this, 'runAfterFilters'));
        $dispatcher->connect('core.exception', array($this, 'handleException'));
        $resolver = new ControllerResolver();

        parent::__construct($dispatcher, $resolver);
    }

    /**
     * Map a pattern to a callable.
     *
     * You can optionally specify HTTP methods that should be matched.
     *
     * This method is chainable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed $to Callback that returns the response when matched
     * @param string $method Matched HTTP methods, multiple can be supplied,
     *                       delimited by a pipe character '|', eg. 'GET|POST'. 
     *
     * @return $this
     */
    public function match($pattern, $to, $method = null)
    {
        $requirements = array();

        if ($method) {
            $requirements['_method'] = $method;
        }

        $routeName = (string) $method . $pattern;
        $routeName = str_replace(array('{', '}'), '', $routeName);
        $routeName = str_replace(array('/', ':', '|'), '_', $routeName);
        $route = new Route($pattern, array('_controller' => $to), $requirements);
        $this->routes->add($routeName, $route);

        return $this;
    }

    /**
     * Map a GET request to a callable.
     *
     * This method is chainable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed $to Callback that returns the response when matched
     *
     * @return $this
     */
    public function get($pattern, $to)
    {
        $this->match($pattern, $to, 'GET');

        return $this;
    }

    /**
     * Map a POST request to a callable.
     *
     * This method is chainable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed $to Callback that returns the response when matched
     *
     * @return $this
     */
    public function post($pattern, $to)
    {
        $this->match($pattern, $to, 'POST');

        return $this;
    }

    /**
     * Map a PUT request to a callable.
     *
     * This method is chainable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed $to Callback that returns the response when matched
     *
     * @return $this
     */
    public function put($pattern, $to)
    {
        $this->match($pattern, $to, 'PUT');

        return $this;
    }

    /**
     * Map a DELETE request to a callable.
     *
     * This method is chainable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed $to Callback that returns the response when matched
     *
     * @return $this
     */
    public function delete($pattern, $to)
    {
        $this->match($pattern, $to, 'DELETE');

        return $this;
    }

    /**
     * Register a before filter.
     *
     * Before filters are run before any route has been matched.
     *
     * This method is chainable.
     *
     * @param mixed $callback Before filter callback
     *
     * @return $this
     */
    public function before($callback)
    {
        $this->handlers['before'][] = $callback;

        return $this;
    }

    /**
     * Register an after filter.
     *
     * After filters are run after the controller has been executed.
     *
     * This method is chainable.
     *
     * @param mixed $callback After filter callback
     *
     * @return $this
     */
    public function after($callback)
    {
        $this->handlers['after'][] = $callback;

        return $this;
    }

    /**
     * Register an error handler.
     *
     * Error handlers are simple callables which take a single Exception
     * as an argument. If a controller throws an exception, an error handler
     * can return a specific response.
     *
     * When an exception occurs, all registered error handlers will be called.
     * The first response a handler returns (it may also return nothing) will
     * then be served.
     *
     * This method is chainable.
     *
     * @param mixed $callback Error handler callback, takes an Exception argument
     *
     * @return $this
     */
    public function error($callback)
    {
        $this->handlers['error'][] = $callback;

        return $this;
    }

    /**
     * Handle the request and deliver the response.
     *
     * @param Request $request Request to process
     *
     * @return $this
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = new Request();
        }

        $this->handle($request)->send();
    }

    /**
     * Parse a route map and create routes
     *
     * @see __construct()
     */
    protected function parseRouteMap(array $map) {
        foreach ($map as $pattern => $to) {
            $method = null;

            if (false !== strpos($pattern, ' ')) {
                list($method, $pattern) = explode(' ', $pattern, 2);
            }

            $this->match($pattern, $to, $method);
        }
    }

    /**
     * Handler for core.request
     *
     * @see __construct()
     */
    public function parseRequest(Event $event)
    {
        $request = $event->get('request');

        $matcher = new UrlMatcher($this->routes, array(
            'base_url'  => $request->getBaseUrl(),
            'method'    => $request->getMethod(),
            'host'      => $request->getHost(),
            'is_secure' => $request->isSecure(),
        ));

        if (false === $attributes = $matcher->match($request->getPathInfo())) {
            return false;
        }

        $request->attributes->add($attributes);
    }

    /**
     * Handler for core.request
     *
     * Runs before filters right after the request comes in.
     *
     * @see __construct()
     */
    public function runBeforeFilters(Event $event)
    {
        foreach ($this->handlers['before'] as $beforeFilter) {
            $beforeFilter();
        }
    }

    /**
     * Handler for core.view
     *
     * Converts string responses to Response objects.
     *
     * @see __construct()
     */
    public function parseResponse(Event $event, $response)
    {
        // convert return value into a response object
        if (!$response instanceof Response) {
            return new Response((string) $response);
        }

        return $response;
    }

    /**
     * Handler for core.view
     *
     * Runs after filters.
     *
     * @see __construct()
     */
    public function runAfterFilters(Event $event, $response)
    {
        foreach ($this->handlers['after'] as $afterFilter) {
            $afterFilter();
        }

        return $response;
    }

    /**
     * Handler for core.exception
     *
     * Executes all registered error handlers and sets the first response
     * to be sent to the client.
     *
     * @see error()
     */
    public function handleException(Event $event)
    {
        $exception = $event->get('exception');
        $prevResult = null;
        foreach ($this->handlers['error'] as $callback) {
            $result = $callback($exception);
            if (null !== $result && !$prevResult) {
                $response = $this->parseResponse($event, $result);
                $event->setReturnValue($response);
                $event->setProcessed(true);
                $prevResult = $result;
            }
        }
    }
}
