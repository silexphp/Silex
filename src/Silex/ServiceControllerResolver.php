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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * Enables name_of_service:method_name syntax for declaring controllers.
 *
 * @link http://silex.sensiolabs.org/doc/providers/service_controller.html
 */
class ServiceControllerResolver implements ControllerResolverInterface
{
    protected $controllerResolver;
    protected $callbackResolver;

    /**
     * Constructor.
     *
     * @param ControllerResolverInterface $controllerResolver A ControllerResolverInterface instance to delegate to
     * @param CallbackResolver             $callbackResolver    A service resolver instance
     */
    public function __construct(ControllerResolverInterface $controllerResolver, CallbackResolver $callbackResolver)
    {
        $this->controllerResolver = $controllerResolver;
        $this->callbackResolver = $callbackResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getController(Request $request)
    {
        $controller = $request->attributes->get('_controller', null);

        if (!$this->callbackResolver->isValid($controller)) {
            return $this->controllerResolver->getController($request);
        }

        return $this->callbackResolver->convertCallback($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(Request $request, $controller)
    {
        return $this->controllerResolver->getArguments($request, $controller);
    }
}
