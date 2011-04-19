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

use Silex\Exception\ControllerFrozenException;

use Symfony\Component\Routing\Route;

/**
 * Mutliple actions collection.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ActionCollection
{
    private $actions = array();

    /**
     * Adds action to collection.
     *
     * @param   callable    $action callable
     */
    public function add($action)
    {
        if (!is_callable($action)) {
            throw new \InvalidArgumentException('Action should be callable');
        }

        $this->actions[] = $action;
    }

    /**
     * Consistently invokes all actions, until some of them returns not null value or list ends up.
     * 
     * @return  mixed
     */
    public function __invoke()
    {
        foreach ($this->actions as $action) {
            if (null !== $return = call_user_func_array($action, func_get_args())) {
                return $return;
            }
        }
    }
}
