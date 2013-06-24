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

class MountController implements AttachableControllerInterface
{
    protected $prefix = '';
    protected $controllers = null;

    public function __construct($prefix, $controllers)
    {
        if ($controllers instanceof ControllerProviderInterface) {
            $controllers = $controllers->connect($this);
        }

        if (!$controllers instanceof ControllerCollection) {
            throw new \LogicException('The "MountController" instance constructor takes either a ControllerCollection or a ControllerProviderInterface instance.');
        }

        $this->prefix = $prefix;
        $this->controllers = $controllers;
    }

    public function attach(RouteCollection $routes, $prefix = '')
    {
        $routes->addCollection($this->controllers->flush($this->prefix));

        return $this;
    }
}