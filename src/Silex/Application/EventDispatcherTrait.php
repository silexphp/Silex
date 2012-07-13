<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Application;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event Dispatcher trait.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
trait EventDispatcherTrait
{
    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string   $eventName The event to listen on
     * @param callable $listener  The listener
     * @param integer  $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function on($eventName, $callback, $priority = 0)
    {
        if (is_array($callback) && 2 === count($callback) && is_string($callback[0]) && isset($this[$callback[0]])) {
            $id = $callback[0];
            $method = $callback[1];
            $app = $this;
            $callback = function (Event $event) use ($app, $id, $method) {
                return call_user_func(array($app[$id], $method), $event);
            };
        }

        $stopCallback = function (Event $event) use ($callback) {
            $ret = call_user_func($callback, $event);

            if (false === $ret) {
                $event->stopPropagation();
            }
        };

        return $this['dispatcher']->addListener($eventName, $stopCallback, $priority);
    }
}
