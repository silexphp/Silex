<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Application;

use Symfony\Component\Validator\Constraint;

/**
 * Symfony Validator component Provider.
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
trait ValidatorTrait
{
    /**
     * Validate the given object.
     *
     * @param object     $object The object to validate
     * @param array|null $groups The validator groups to use for validating
     *
     * @return ConstraintViolationList
     */
    public function validate($object, $groups = null)
    {
        return $this['validator']->validate($object, $groups);
    }

    /**
     * Validate a single property of an object against its current value.
     *
     * @param object     $object   The object to validate
     * @param string     $property The name of the property to validate
     * @param array|null $groups   The validator groups to use for validating
     *
     * @return ConstraintViolationList
     */
    public function validateProperty($object, $property, $groups = null)
    {
        return $this['validator']->validateProperty($object, $property, $groups);
    }

    /**
     * Validate a single property of an object against the given value.
     *
     * @param string     $class    The class on which the property belongs
     * @param string     $property The name of the property to validate
     * @param string     $value
     * @param array|null $groups   The validator groups to use for validating
     *
     * @return ConstraintViolationList
     */
    public function validatePropertyValue($class, $property, $value, $groups = null)
    {
        return $this['validator']->validatePropertyValue($class, $property, $value, $groups);
    }

    /**
     * Validates a given value against a specific Constraint.
     *
     * @param mixed      $value      The value to validate
     * @param Constraint $constraint The constraint to validate against
     * @param array|null $groups     The validator groups to use for validating
     *
     * @return ConstraintViolationList
     */
    public function validateValue($value, Constraint $constraint, $groups = null)
    {
        return $this['validator']->validateValue($value, $constraint, $groups);
    }
}
