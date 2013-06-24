<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Marcin Chwedziak <marcin@chwedziak.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\Routing\RouteCollection;

interface AttachableControllerInterface
{
    /**
     * Attaches given controller to a collection of routes
     *
     * @param RouteCollection $routes Collection to which you want to attach the controller
     * @param string $prefix
     *
     * @return AttachableControllerInterface
     */
    function attach(RouteCollection $routes, $prefix = '');
}