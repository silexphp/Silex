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

use Symfony\Component\Form\FormBuilder;

/**
 * Form trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait FormTrait
{
    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    public function form($data = null, array $options = array())
    {
        $name = 'Symfony\Component\Form\Extension\Core\Type\FormType';
        // BC with Symfony < 2.8
        if (!class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType')) {
            $name = 'form';
        }

        return $this['form.factory']->createBuilder($name, $data, $options);
    }
}
