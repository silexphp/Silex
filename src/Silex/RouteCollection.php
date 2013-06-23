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
    /**
     * @var bool
     */
    protected $prioritized = false;

    public function getIterator()
    {
        $routes = $this->all();

        $this->prioritize($routes);
        
        return new \ArrayIterator($routes);
    }

    public function add($name, BaseRoute $route)
    {
        parent::add($name, $route);

        $this->prioritized = false;
    }

    public function addCollection(BaseRouteCollection $collection)
    {
        parent::addCollection($collection);

        $this->prioritized = false;
    }

    public function addOptions(array $options)
    {
        parent::addOptions($options);

        $this->prioritized = false;
    }

    protected function prioritize(&$routes)
    {
        if ($this->prioritized) {
            return;
        }

        uasort($routes, function($a, $b){
            return $a->getOption('priority') > $b->getOption('priority') ? -1 : 1;
        });

        $this->prioritized = true;
    }
}