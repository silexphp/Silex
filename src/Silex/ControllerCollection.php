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

/**
 * A collection of Silex controllers.
 *
 * It acts as a staging area for routes. You are able to set the route name
 * until flush() is called, at which point all controllers are frozen and
 * added to the RouteCollection.
 *
 * @author Igor Wiedler igor@wiedler.ch
 */
class ControllerCollection
{
    private $controllers = array();
    private $routeCollection;

    public function __construct(RouteCollection $routeCollection)
    {
        $this->routeCollection = $routeCollection;
    }

    /**
     * Add a controller to the staging area.
     *
     * @param Controller $controller
     */
    public function add(Controller $controller)
    {
        $this->controllers[] = $controller;
    }

    /**
     * Persist and freeze staged controllers.
     */
    public function flush()
    {
        foreach ($this->controllers as $controller) {
            $this->routeCollection->add($controller->getRouteName(), $controller->getRoute());
            $controller->freeze();
        }

        $this->controllers = array();
    }
}
