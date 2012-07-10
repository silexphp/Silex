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

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Pimple;

/**
 * Wraps listeners in closures to allow lazy loading of services
 *
 * @author Dave Marshall <dave.marshall@atstsolutions.co.uk>
 */
class PimpleAwareEventDispatcher extends EventDispatcher
{
    protected $app;

    protected $listenerIds = array();

    /**
     * Constructor.
     *
     * @param Pimole $app
     */
    public function __construct(Pimple $app)
    {
        $this->app = $app;
    }

    /**
     * Adds a service as event listener
     *
     * @param string $eventName Event for which the listener is added
     * @param array $callback The service ID of the listener service & the method
     * name that has to be called
     * @param integer $priority The higher this value, the earlier an event listener
     * will be triggered in the chain.
     * Defaults to 0.
     */
    public function addListenerService($eventName, $callback, $priority = 0)
    {
        if (!is_array($callback) || 2 !== count($callback)) {
            throw new \InvalidArgumentException('Expected an array("service", "method") argument');
        }

        $serviceId = $callback[0];
        $method = $callback[1];
        $app = $this->app;
        $closure = function(Event $e) use ($app, $serviceId, $method) {
            call_user_func(array($app[$serviceId], $method), $e);
        };

        $this->listenerIds[$eventName][] = array($callback, $closure);
        $this->addListener($eventName, $closure, $priority);
    }

    public function removeListener($eventName, $listener)
    {
        foreach ($this->listenerIds[$eventName] as $i => $parts) {
            list($callback, $closure) = $parts;
            if ($listener == $callback) {
                $listener = $closure;
                break;
            }
        }
        parent::removeListener($eventName, $listener);
    }

    /**
     * Adds a service as event subscriber
     *
     * @param string $serviceId The service ID of the subscriber service
     * @param string $class The service's class name (which must implement EventSubscriberInterface)
     */
    public function addSubscriberService($serviceId, $class)
    {
        foreach ($class::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListenerService($eventName, array($serviceId, $params), 0);
            } elseif (is_string($params[0])) {
                $this->addListenerService($eventName, array($serviceId, $params[0]), isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListenerService($eventName, array($serviceId, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }

    }
}
