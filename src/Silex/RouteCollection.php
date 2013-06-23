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
        $temp = array();
        $i = 0;
        foreach ($routes as $name => $route) {
            $temp[] = array($i++, $name, $route);
        }

        usort($temp, function(&$a, &$b){
            if ($b[2]->getOption('priority') == $a[2]->getOption('priority')) {
                return $a[0] - $b[1];
            }
            return $b[2]->getOption('priority') - $a[2]->getOption('priority');
        });

        $sorted = array();
        foreach ($temp as $route) {
            $sorted[$route[1]] = $route[2];
        }

        return $sorted;
    }
}