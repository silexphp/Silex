<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Application;

use PHPUnit\Framework\TestCase;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Form\FormBuilder;

/**
 * FormTrait test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FormTraitTest extends TestCase
{
    public function testForm()
    {
        $this->assertInstanceOf(FormBuilder::class, $this->createApplication()->form());
    }

    public function testNamedForm()
    {
        $builder = $this->createApplication()->namedForm('foo');

        $this->assertInstanceOf(FormBuilder::class, $builder);
        $this->assertSame('foo', $builder->getName());
    }

    public function createApplication()
    {
        $app = new FormApplication();
        $app->register(new FormServiceProvider());

        return $app;
    }
}
