<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Provider\Psr11;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * Resolves ContainerInterface arguments when using an old (<3.1) kernel.
 *
 * @author Pascal Luna <skalpa@zetareticuli.org>
 */
final class ControllerResolver implements ArgumentResolverInterface, ControllerResolverInterface
{
    private $resolver;
    private $container;

    public function __construct(ControllerResolverInterface $resolver, ContainerInterface $container)
    {
        $this->resolver = $resolver;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getController(Request $request)
    {
        return $this->resolver->getController($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(Request $request, $controller)
    {
        if (is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && !$controller instanceof \Closure) {
            $r = new \ReflectionObject($controller);
            $r = $r->getMethod('__invoke');
        } else {
            $r = new \ReflectionFunction($controller);
        }
        $this->resolveArguments($request, $r->getParameters());

        return $this->resolver->getArguments($request, $controller);
    }

    private function resolveArguments(Request $request, array $parameters)
    {
        foreach ($parameters as $param) {
            if (!$request->attributes->has($param->name) && $param->getClass() && is_a($this->container, $param->getClass()->name)) {
                $request->attributes->set($param->name, $this->container);
            }
        }
    }
}
