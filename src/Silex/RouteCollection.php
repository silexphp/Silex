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
        return new \ArrayIterator(static::stableSort($this->all()));
    }

    protected static function stableSort($routes)
    {
        $i = 0;
        array_walk($routes, function(&$v, $k) use (&$i) {
            $v = array(-$v->getOption('priority'), $i++, $v);
        });
        asort($routes);
        array_walk($routes, function(&$v, $k){ $v = $v[2]; });

        return $routes;
    }
}