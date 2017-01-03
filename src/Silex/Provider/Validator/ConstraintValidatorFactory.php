<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Validator;

use Pimple\Container;
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
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $serviceNames;

    /**
     * Constructor.
     *
     * @param Container $container    DI container
     * @param array     $serviceNames Validator service names
     */
    public function __construct(Container $container, array $serviceNames = array(), $propertyAccessor = null)
    {
        parent::__construct($propertyAccessor);

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
