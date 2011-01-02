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
 * This file is part of the Goutte utility.
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

    public function __construct(array $map = null)
    {
        $this->routes = new RouteCollection();

        if ($map) {
            $this->parseRouteMap($map);
        }

        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.request', array($this, 'parseRequest'));
        $dispatcher->connect('core.view', array($this, 'parseResponse'));
        $resolver = new ControllerResolver();

        parent::__construct($dispatcher, $resolver);
    }

    public function match($pattern, $to, $method = null)
    {
        $requirements = array();

        if ($method) {
            $requirements['_method'] = $method;
        }

        $route = new Route($pattern, array('_controller' => $to), $requirements);
        $this->routes->add(str_replace(array('/', ':', '|'), '_', (string) $method . $pattern), $route);

        return $this;
    }

    public function get($pattern, $to)
    {
        $this->match($pattern, $to, 'GET');

        return $this;
    }

    public function post($pattern, $to)
    {
        $this->match($pattern, $to, 'POST');

        return $this;
    }

    public function put($pattern, $to)
    {
        $this->match($pattern, $to, 'PUT');

        return $this;
    }

    public function delete($pattern, $to)
    {
        $this->match($pattern, $to, 'DELETE');

        return $this;
    }

    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = new Request();
        }

        $this->handle($request)->send();
    }

    protected function parseRouteMap(array $map) {
        foreach ($map as $pattern => $to) {
            $method = null;

            if (false !== strpos($pattern, ' ')) {
                list($method, $pattern) = explode(' ', $pattern, 2);
            }

            $this->match($pattern, $to, $method);
        }
    }

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

    public function parseResponse(Event $event, $response)
    {
        // convert return value into a response object
        if (!$response instanceof Response) {
            return new Response((string) $response);
        }

        return $response;
    }

    public static function create(array $map = null)
    {
        return new static($map);
    }
}
