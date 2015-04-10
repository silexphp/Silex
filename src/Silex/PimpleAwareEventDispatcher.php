<?php


namespace Silex;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Pimple\Container;

/**
 * Class PimpleAwareEventDispatcher
 * @package Silex
 */
final class PimpleAwareEventDispatcher extends EventDispatcher
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $listener_ids = [];

    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $event_name
     * @param $callback
     * @param int $priority
     */
    public function addListenerService($event_name, $callback, $priority = 0)
    {
        if (!is_array($callback) || count($callback) !== 2) {
            throw new \InvalidArgumentException("Expected an array('service', 'method') argument");
        }

        $this->listener_ids[$event_name][] = [$callback[0], $callback[1], $priority];
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener($event_name, $listener)
    {
        $this->lazyLoad($event_name);

        if (isset($this->listener_ids[$event_name])) {
            foreach ($this->listener_ids[$event_name] as $i => $args) {
                list($service_id, $method) = $args;

                $key = $service_id . "." . $method;

                if (isset($this->listeners[$event_name][$key]) && $listener === [
                        $this->listeners[$event_name][$key],
                        $method
                    ]
                ) {
                    unset($this->listeners[$event_name][$key]);

                    if (empty($this->listeners[$event_name])) {
                        unset($this->listeners[$event_name]);
                    }

                    unset($this->listener_ids[$event_name][$i]);

                    if (empty($this->listener_ids[$event_name])) {
                        unset($this->listener_ids[$event_name]);
                    }
                }
            }
        }

        parent::removeListener($event_name, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners($event_name = null)
    {
        if ($event_name === null) {
            return (bool)count($this->listener_ids) || (bool)count($this->listeners);
        }

        if (isset($this->listener_ids[$event_name])) {
            return true;
        }

        return parent::hasListeners($event_name);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($event_name = null)
    {
        if ($event_name === null) {
            foreach (array_keys($this->listener_ids) as $service_event_name) {
                $this->lazyLoad($service_event_name);
            }
        } else {
            $this->lazyLoad($event_name);
        }

        return parent::getListeners($event_name);
    }

    /**
     * @param $service_id
     * @param $class
     */
    public function addSubscriberService($service_id, $class)
    {
        foreach ($class::getSubscribedEvents() as $event_name => $params) {
            if (is_string($params)) {
                $this->listener_ids[$event_name][] = [$service_id, $params, 0];
            } elseif (is_string($params[0])) {
                $this->listener_ids[$event_name][] = [$service_id, $params[0], isset($params[1]) ? $params[1] : 0];
            } else {
                foreach ($params as $listener) {
                    $this->listener_ids[$event_name][] = [
                        $service_id,
                        $listener[0],
                        isset($listener[1]) ? $listener[1] : 0
                    ];
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($event_name, Event $event = null)
    {
        $this->lazyLoad($event_name);

        return parent::dispatch($event_name, $event);
    }

    /**
     * @param $event_name
     */
    private function lazyLoad($event_name)
    {
        if (isset($this->listener_ids[$event_name])) {
            foreach ($this->listener_ids[$event_name] as $args) {
                list($service_id, $method, $priority) = $args;

                $listener = $this->container->offsetGet($service_id);

                $key = $service_id . "." . $method;

                if (! isset($this->listeners[$event_name][$key])) {
                    $this->addListener($event_name, [$listener, $method], $priority);
                } elseif ($listener !== $this->listeners[$event_name][$key]) {
                    parent::removeListener($event_name, [$this->listeners[$event_name][$key], $method]);
                    $this->addListener($event_name, [$listener, $method], $priority);
                }

                $this->listeners[$event_name][$key] = $listener;
            }
        }
    }
}
