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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Wraps view listeners.
 *
 * @author Dave Marshall <dave@atst.io>
 */
class ViewListenerWrapper
{
    protected $app;
    protected $callback;

    /**
     * Constructor.
     *
     * @param Application $app      An Application instance
     * @param callable    $callback
     */
    public function __construct(Application $app, $callback)
    {
        $this->app = $app;
        $this->callback = $callback;
    }

    public function __invoke(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $this->callback = $this->app['callback_resolver']->resolveCallback($this->callback);

        if (!$this->shouldRun($controllerResult)) {
            return;
        }

        $response = call_user_func($this->callback, $controllerResult, $event->getRequest());

        if ($response instanceof Response) {
            $event->setResponse($response);
        } else {
            $event->setControllerResult($response);
        }
    }

    protected function shouldRun($controllerResult)
    {
        if (is_array($this->callback)) {
            $callbackReflection = new \ReflectionMethod($this->callback[0], $this->callback[1]);
        } elseif (is_object($this->callback) && !$this->callback instanceof \Closure) {
            $callbackReflection = new \ReflectionObject($this->callback);
            $callbackReflection = $callbackReflection->getMethod('__invoke');
        } else {
            $callbackReflection = new \ReflectionFunction($this->callback);
        }

        if ($callbackReflection->getNumberOfParameters() > 0) {
            $parameters = $callbackReflection->getParameters();
            $expectedControllerResult = $parameters[0];

            if ($expectedControllerResult->getClass() && (!is_object($controllerResult) || !$expectedControllerResult->getClass()->isInstance($controllerResult))) {
                return false;
            }

            if ($expectedControllerResult->isArray() && !is_array($controllerResult)) {
                return false;
            }

            if (method_exists($expectedControllerResult, 'isCallable') && $expectedControllerResult->isCallable() && !is_callable($controllerResult)) {
                return false;
            }
        }

        return true;
    }

}
