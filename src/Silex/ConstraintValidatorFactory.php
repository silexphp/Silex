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
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Uses a service container to create constraint validators with dependencies.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 * @author Alex Kalyvitis <alex.kalyvitis@gmail.com>
 */
class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
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
     * @var array
     */
    protected $validators;

    /**
     * Constructor
     *
     * @param \Pimple $container    DI container
     * @param array   $serviceNames Validator service names
     */
    public function __construct(\Pimple $container, array $serviceNames = array())
    {
        $this->container    = $container;
        $this->serviceNames = $serviceNames;
        $this->validators   = array();
    }

    /**
     * Returns the validator for the supplied constraint.
     *
     * @param  Constraint          $constraint A constraint
     * @return ConstraintValidator A validator for the supplied constraint
     */
    public function getInstance(Constraint $constraint)
    {
        $name = $constraint->validatedBy();
        
        // Quoting `webmozart` on the symfony/symfony project:
        // https://github.com/symfony/symfony/commit/d4ebbfd02d416504ebfed262d656941062905b76#diff-3a3e44a703775a35fbdd66850a43968dR41
        // 
        // " The second condition is a hack that is needed when CollectionValidator
        //   calls itself recursively (Collection constraints can be nested).
        //   Since the context of the validator is overwritten when initialize()
        //   is called for the nested constraint, the outer validator is
        //   acting on the wrong context when the nested validation terminates. "
        // 
        // Note: This also applies to AllValidator and All constraints, hence 
        //       the additional third condition hack.
        if (isset($this->validators[$name]) && 
            $name != 'Symfony\Component\Validator\Constraints\CollectionValidator' && 
            $name != 'Symfony\Component\Validator\Constraints\AllValidator') {
            return $this->validators[$name];
        }

        $this->validators[$name] = $this->createValidator($name);

        return $this->validators[$name];
    }

    /**
     * Returns the validator instance
     *
     * @param  string              $name
     * @return ConstraintValidator
     */
    private function createValidator($name)
    {
        if (isset($this->serviceNames[$name])) {
            return $this->container[$this->serviceNames[$name]];
        }

        return new $name();
    }
}
