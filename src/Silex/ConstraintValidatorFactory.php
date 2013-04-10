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

use Silex\Application;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Uses a service container to create constraint validators with dependencies.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    protected $container;
    protected $validators;

    /**
     * Constructor.
     *
     * @param Silex\Application $container  A DI container
     * @param array             $validators An array of validators
     */
    public function __construct(Application $container, array $validators = array())
    {
        $this->container = $container;
        $this->validators = $validators;
    }

    /**
     * Returns the validator for the supplied constraint.
     *
     * @param Constraint $constraint A constraint
     *
     * @return ConstraintValidator A validator for the supplied constraint
     */
    public function getInstance(Constraint $constraint)
    {
        $name = $constraint->validatedBy();

        if (!isset($this->validators[$name])) {
            $validator = new $name();
        } elseif (is_string($this->validators[$name])) {
            $validator = $this->container[$this->validators[$name]];
        }

        return $validator;
    }
}
