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

use Symfony\Component\Routing\Route as BaseRoute;
use Symfony\Component\Routing\RouteCollection as BaseRouteCollection;

class RouteCollection extends BaseRouteCollection
{
    public function getIterator()
    {
        $routes = $this->all();

        uasort($routes, function($a, $b){
            if ($a->getOption('priority') > $b->getOption('priority')) {
                return -1;
            }

            return 1;
        });
        
        return new \ArrayIterator($routes);
    }
}