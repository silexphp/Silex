<?php

namespace Silex\ArgumentResolver;

use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\ArgumentResolverInterface;

class ApplicationArgumentResolver implements ArgumentResolverInterface
{
    private $pimple;

    /**
     * @param Container $pimple
     */
    public function __construct(Container $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, \ReflectionParameter $parameter)
    {
        $class = $parameter->getClass();

        return $class && $class->isInstance($this->pimple);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, \ReflectionParameter $parameter)
    {
        return $this->pimple;
    }
}
