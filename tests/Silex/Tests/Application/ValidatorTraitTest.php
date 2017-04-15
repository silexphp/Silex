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

use Silex\Application;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Tests\Fixtures\Entity;
use Symfony\Component\Validator\Tests\Fixtures\FakeMetadataFactory;
use Symfony\Component\Validator\Tests\Fixtures\FakeClassMetadataFactory; // Symfony 2.1 only
use Symfony\Component\Validator\Tests\Fixtures\FailingConstraint;

/**
 * ValidatorTrait test cases.
 *
 * @author Ludovic Fleury <ludo.fleur@gmail.com>
 *
 * @requires PHP 5.4
 */
class ValidatorTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testValidate()
    {
        $app = $this->createApplication();

        $stub = $this->getMock('TestClass');
        $stub::staticExpects($this->any())
             ->method('loadValidatorMetadata');

        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $app->validate($stub));
    }

    public function testValidateValue()
    {
        $app = $this->createApplication();

        $this->assertInstanceOf(
            'Symfony\Component\Validator\ConstraintViolationList',
            $app->validateValue('value', new \Symfony\Component\Validator\Tests\Fixtures\ConstraintA())
        );
    }

    public function testValidateProperty()
    {
        $app = $this->createApplication();

        $entity = new Entity();
        $metadata = new ClassMetadata(get_class($entity));
        $metadata->addPropertyConstraint('firstName', new FailingConstraint());
        $this->fakeMetadata($app, $metadata);

        $result = $app->validateProperty($entity, 'firstName');

        $this->assertCount(1, $result);
    }

    public function testValidatePropertyValue()
    {
        $app = $this->createApplication();

        $entity = new Entity();
        $metadata = new ClassMetadata(get_class($entity));
        $metadata->addPropertyConstraint('firstName', new FailingConstraint());
        $this->fakeMetadata($app, $metadata);

        $result = $app->validatePropertyValue(get_class($entity), 'firstName', 'Bernhard');

        $this->assertCount(1, $result);
    }

    public function createApplication()
    {
        $app = new ValidatorApplication();
        $app->register(new ValidatorServiceProvider());

        return $app;
    }

    public function fakeMetadata(Application $app, ClassMetadata $metadata)
    {
        if (!class_exists('Symfony\Component\Validator\Tests\Fixtures\FakeMetadataFactory')) {
            $symfonyVersion = '2.1';
        } else {
            $symfonyVersion = '2.2';
        }

        $app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) use ($symfonyVersion) {
            return '2.1' === $symfonyVersion ? new FakeClassMetadataFactory() : new FakeMetadataFactory();
        });

        if ('2.1' === $symfonyVersion) {
            $app['validator.mapping.class_metadata_factory']->addClassMetadata($metadata);
        } else {
            $app['validator.mapping.class_metadata_factory']->addMetadata($metadata);
        }
    }

}
