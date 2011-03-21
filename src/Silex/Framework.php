<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher;

/**
 * The Silex framework class.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class Framework extends HttpKernel
{
    protected $routes;
    protected $request;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->routes = new RouteCollection();

        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.request', array($this, 'parseRequest'));
        $dispatcher->connect('core.request', array($this, 'runBeforeFilters'));
        $dispatcher->connect('core.view', array($this, 'handleStringResponse'), -10);
        $dispatcher->connect('core.response', array($this, 'runAfterFilters'));
        $dispatcher->connect('core.exception', array($this, 'handleException'));
        $resolver = new ControllerResolver();

        parent::__construct($dispatcher, $resolver);
    }

    /**
     * Get the current request.
     *
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
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
        $this->dispatcher->connect('silex.before', $callback);

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
        $this->dispatcher->connect('silex.after', $callback);

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
        $this->dispatcher->connect('silex.error', function(EventInterface $event) use ($callback) {
            $exception = $event->get('exception');
            $result = $callback($exception);

            if (null !== $result) {
                $event->setProcessed();
                return $result;
            }
        });

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
            $request = Request::createFromGlobals();
        }

        $this->handle($request)->send();
    }

    /**
     * Handler for core.request
     *
     * @see __construct()
     */
    public function parseRequest(EventInterface $event)
    {
        $this->request = $event->get('request');

        $matcher = new UrlMatcher($this->routes, array(
            'base_url'  => $this->request->getBaseUrl(),
            'method'    => $this->request->getMethod(),
            'host'      => $this->request->getHost(),
            'is_secure' => $this->request->isSecure(),
        ));

        if (false === $attributes = $matcher->match($this->request->getPathInfo())) {
            return false;
        }

        $this->request->attributes->add($attributes);
    }

    /**
     * Handler for core.request
     *
     * Runs before filters right after the request comes in.
     *
     * @see __construct()
     */
    public function runBeforeFilters(EventInterface $event)
    {
        $this->dispatcher->notify(new Event(null, 'silex.before'));
    }

    /**
     * Handler for core.view
     *
     * Calls parseStringResponse to handle string responses.
     *
     * @see __construct()
     * @see parseStringResponse()
     */
    public function handleStringResponse(EventInterface $event)
    {
        $response = $event->get('controller_value');
        if ( ! $response instanceof Response) {
            $event->setProcessed(true);
            return $this->parseStringResponse($response);
        }
    }

    /**
     * Converts string responses to Response objects.
     */
    protected function parseStringResponse($response)
    {
        if ( ! $response instanceof Response) {
            return new Response((string) $response);
        } else {
            return $response;
        }
    }

    /**
     * Handler for core.view
     *
     * Runs after filters.
     *
     * @see __construct()
     */
    public function runAfterFilters(EventInterface $event, $response)
    {
        $this->dispatcher->notify(new Event(null, 'silex.after'));

        return $response;
    }

    /**
     * Handler for core.exception
     *
     * Executes registered error handlers until a response is returned,
     * in which case it returns it to the client.
     *
     * @see error()
     */
    public function handleException(EventInterface $event)
    {
        $errorEvent = new Event(null, 'silex.error', $event->all());
        $result = $this->dispatcher->notifyUntil($errorEvent);

        if ($errorEvent->isProcessed()) {
            $event->setProcessed();
            $response = $this->parseStringResponse($result);
            return $response;
        }
    }
}
