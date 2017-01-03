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

use Symfony\Component\Form;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver\FormTypeInterface;

/**
 * Form trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author David Berlioz <berliozdavid@gmail.com>
 */
trait FormTrait
{
    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     * @param string|FormTypeInterface $type    Type of the form
     *
     * @return FormBuilder
     */
    public function form($data = null, array $options = array(), $type = null)
    {
        return $this['form.factory']->createBuilder($type ?: FormType::class, $data, $options);
    }

    /**
     * Creates and returns a named form builder instance.
     *
     * @param string                   $name
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     * @param string|FormTypeInterface $type    Type of the form
     *
     * @return FormBuilder
     */
    public function namedForm($name, $data = null, array $options = array(), $type = null)
    {
        return $this['form.factory']->createNamedBuilder($name, $type ?: FormType::class, $data, $options);
    }
}
