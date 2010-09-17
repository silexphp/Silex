<?php

namespace Silex;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
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

    public function __construct($map)
    {
        $this->routes = new RouteCollection();
        foreach ($map as $pattern => $to) {
            if (false !== strpos($pattern, ' ')) {
                list($method, $pattern) = explode(' ', $pattern, 2);
            } else {
                $method = 'GET';
            }

            $route = new Route($pattern, array('_controller' => $to), array('_method' => explode('|', $method)));
            $this->routes->addRoute(str_replace(array('/', ':'), '_', $pattern), $route);
        }

        $dispatcher = new EventDispatcher();
        $dispatcher->connect('core.request', array($this, 'parseRequest'));
        $resolver = new ControllerResolver();

        parent::__construct($dispatcher, $resolver);
    }

    public function parseRequest(Event $event)
    {
        $request = $event['request'];

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
}
