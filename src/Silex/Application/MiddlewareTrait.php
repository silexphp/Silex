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

use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Middleware trait.
 *
 * @author Chris Heng <hengkuanyen@gmail.com>
 */
trait MiddlewareTrait
{
    /**
     * Utilize generator style middleware.
     *
     * @param callable|array $callback
     * @param integer|array  $priorities
     */
    public function utilize($callback, $priorities = 0)
    {
        static $counter;

        if (is_array($callback) && !is_callable($callback)) {
            foreach ($callback as $priority => $callable) {
                $this->utilize($callable, $priority);
            }

            return $this;
        }

        $resolver = $this['callback_resolver'];

        $listener = function ($event) use ($callback, $resolver, &$counter) {
            static $generators = array();

            if (isset($generators[$counter])) {
                $generator = $generators[$counter];

                return $generator->send($event);
            }

            $ret = call_user_func($resolver->resolveCallback($callback), $event);

            if ($ret instanceof \Continuation || $ret instanceof \Generator) {
                $generator = $generators[$counter] = $ret;

                if ($generator instanceof \Continuation) {
                    $generator->next();
                }

                return $generator->current();
            }
        };

        if (!is_array($priorities)) {
            $priority = (int) $priorities;
            $priorities = array(
                KernelEvents::REQUEST => $priority,
                KernelEvents::RESPONSE => -$priority
            );
        }

        $before = function () use (&$counter) {
            $counter++;
        };

        $after = function () use (&$counter) {
            $counter--;
        };

        if ($this->booted) {
            foreach ($priorities as $eventName => $priority) {
                $this['dispatcher']->addListener($eventName, $listener, $priority);
            }
            if (null === $counter) {
                $this['dispatcher']->addListener(KernelEvents::REQUEST, $before, 9999);
                $this['dispatcher']->addListener(KernelEvents::FINISH_REQUEST, $after, -9999);
                $counter = 0;
            }
        } else {
            $this['dispatcher'] = $this->share(
                $this->extend(
                    'dispatcher',
                    function ($dispatcher, $app) use ($priorities, $listener, &$counter, $before, $after) {
                        foreach ($priorities as $eventName => $priority) {
                            $dispatcher->addListener($eventName, $listener, $priority);
                        }
                        if (null === $counter) {
                            $dispatcher->addListener(KernelEvents::REQUEST, $before, 9999);
                            $dispatcher->addListener(KernelEvents::FINISH_REQUEST, $after, -9999);
                            $counter = 0;
                        }

                        return $dispatcher;
                    }
                )
            );
        }

        return $this;
    }
}
