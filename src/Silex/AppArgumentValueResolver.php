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
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * HttpKernel Argument Resolver for Silex.
 *
 * @author Romain Neutron <imprec@gmail.com>
 */
class AppArgumentValueResolver implements ArgumentValueResolverInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getType() === Application::class || (null !== $argument->getType() && in_array(Application::class, class_parents($argument->getType()), true));
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->app;
    }
}
