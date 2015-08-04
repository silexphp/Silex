<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory as BaseConstraintValidatorFactory;

/**
 * Uses a service container to create constraint validators with dependencies.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 * @author Alex Kalyvitis <alex.kalyvitis@gmail.com>
 */
class ConstraintValidatorFactory extends BaseConstraintValidatorFactory
{
    /**
     * @var \Pimple
     */
    protected $container;

    /**
     * @var array
     */
    protected $serviceNames;

    /**
     * Constructor.
     *
     * @param \Pimple $container    DI container
     * @param array   $serviceNames Validator service names
     */
    public function __construct(\Pimple $container, array $serviceNames = array(), $propertyAccessor = null)
    {
        // for BC with 2.3
        if (method_exists('Symfony\Component\Validator\Constraint\BaseConstraintValidatorFactory', '__construct')) {
            parent::__construct($propertyAccessor);
        }

        $this->container = $container;
        $this->serviceNames = $serviceNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance(Constraint $constraint)
    {
        $name = $constraint->validatedBy();

        if (isset($this->serviceNames[$name])) {
            return $this->container[$this->serviceNames[$name]];
        }

        return parent::getInstance($constraint);
    }
}
