<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Validator\Constraints as Assert;
use Silex\Tests\Provider\ValidatorServiceProviderTest\Constraint\Custom;
use Silex\Tests\Provider\ValidatorServiceProviderTest\Constraint\CustomValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * ValidatorServiceProvider.
 *
 * Javier Lopez <f12loalf@gmail.com>
 */
class ValidatorServiceProviderTest extends TestCase
{
    public function testRegister()
    {
        $app = new Application();
        $app->register(new ValidatorServiceProvider());
        $app->register(new FormServiceProvider());

        return $app;
    }

    public function testRegisterWithCustomValidators()
    {
        $app = new Application();

        $app['custom.validator'] = function () {
            return new CustomValidator();
        };

        $app->register(new ValidatorServiceProvider(), [
            'validator.validator_service_ids' => [
                'test.custom.validator' => 'custom.validator',
            ],
        ]);

        return $app;
    }

    /**
     * @depends testRegisterWithCustomValidators
     */
    public function testConstraintValidatorFactory($app)
    {
        $this->assertInstanceOf('Silex\Provider\Validator\ConstraintValidatorFactory', $app['validator.validator_factory']);

        $validator = $app['validator.validator_factory']->getInstance(new Custom());
        $this->assertInstanceOf('Silex\Tests\Provider\ValidatorServiceProviderTest\Constraint\CustomValidator', $validator);
    }

    /**
     * @depends testRegister
     */
    public function testConstraintValidatorFactoryWithExpression($app)
    {
        $constraint = new Assert\Expression('true');
        $validator = $app['validator.validator_factory']->getInstance($constraint);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\ExpressionValidator', $validator);
    }

    /**
     * @depends testRegister
     */
    public function testValidatorServiceIsAValidator($app)
    {
        $this->assertTrue($app['validator'] instanceof ValidatorInterface);
    }

    /**
     * @depends testRegister
     * @dataProvider getTestValidatorConstraintProvider
     */
    public function testValidatorConstraint($email, $isValid, $nbGlobalError, $nbEmailError, $app)
    {
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
        ]);

        $builder = $app['form.factory']->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [], [
            'constraints' => $constraints,
        ]);

        $form = $builder
            ->add('email', 'Symfony\Component\Form\Extension\Core\Type\EmailType', ['label' => 'Email'])
            ->getForm()
        ;

        $form->submit(['email' => $email]);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertCount($nbGlobalError, $form->getErrors());
        $this->assertCount($nbEmailError, $form->offsetGet('email')->getErrors());
    }

    public function testValidatorWillNotAddNonexistentTranslationFiles()
    {
        $app = new Application([
            'locale' => 'nonexistent',
        ]);

        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider(), [
            'locale_fallbacks' => [],
        ]);

        $app['validator'];
        $translator = $app['translator'];

        try {
            $translator->trans('test');
            $this->addToAssertionCount(1);
        } catch (NotFoundResourceException $e) {
            $this->fail('Validator should not add a translation resource that does not exist');
        }
    }

    public function getTestValidatorConstraintProvider()
    {
        // Email, form is valid, nb global error, nb email error
        return [
            ['', false, 0, 1],
            ['not an email', false, 0, 1],
            ['email@sample.com', true, 0, 0],
        ];
    }

    /**
     * @dataProvider getAddResourceData
     */
    public function testAddResource($registerValidatorFirst)
    {
        $app = new Application();
        $app['locale'] = 'fr';

        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app->extend('translator', function ($translator, $app) {
            $translator->addResource('array', ['This value should not be blank.' => 'Pas vide'], 'fr', 'validators');

            return $translator;
        });

        if ($registerValidatorFirst) {
            $app['validator'];
        }

        $this->assertEquals('Pas vide', $app['translator']->trans('This value should not be blank.', [], 'validators', 'fr'));
    }

    public function getAddResourceData()
    {
        return [[false], [true]];
    }

    public function testAddResourceAlternate()
    {
        $app = new Application();
        $app['locale'] = 'fr';

        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app->factory($app->extend('translator.resources', function ($resources, $app) {
            $resources = array_merge($resources, [
                ['array', ['This value should not be blank.' => 'Pas vide'], 'fr', 'validators'],
            ]);

            return $resources;
        }));

        $app['validator'];

        $this->assertEquals('Pas vide', $app['translator']->trans('This value should not be blank.', [], 'validators', 'fr'));
    }

    public function testTranslatorResourcesIsArray()
    {
        $app = new Application();
        $app['locale'] = 'fr';

        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider());

        $this->assertInternalType('array', $app['translator.resources']);
    }
}
