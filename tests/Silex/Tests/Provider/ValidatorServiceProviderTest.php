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
use Symfony\Component\Validator\ValidatorInterface as LegacyValidatorInterface;
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

        $app->register(new ValidatorServiceProvider(), array(
            'validator.validator_service_ids' => array(
                'test.custom.validator' => 'custom.validator',
            ),
        ));

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
        $this->assertTrue($app['validator'] instanceof ValidatorInterface || $app['validator'] instanceof LegacyValidatorInterface);
    }

    /**
     * @depends testRegister
     * @dataProvider getTestValidatorConstraintProvider
     */
    public function testValidatorConstraint($email, $isValid, $nbGlobalError, $nbEmailError, $app)
    {
        $constraints = new Assert\Collection(array(
            'email' => array(
                new Assert\NotBlank(),
                new Assert\Email(),
            ),
        ));

        $builder = $app['form.factory']->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', array(), array(
            'constraints' => $constraints,
        ));

        $form = $builder
            ->add('email', 'Symfony\Component\Form\Extension\Core\Type\EmailType', array('label' => 'Email'))
            ->getForm()
        ;

        $form->submit(array('email' => $email));

        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($nbGlobalError, count($form->getErrors()));
        $this->assertEquals($nbEmailError, count($form->offsetGet('email')->getErrors()));
    }

    public function testValidatorWillNotAddNonexistentTranslationFiles()
    {
        $app = new Application(array(
            'locale' => 'nonexistent',
        ));

        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider(), array(
            'locale_fallbacks' => array(),
        ));

        $app['validator'];
        $translator = $app['translator'];

        try {
            $translator->trans('test');
        } catch (NotFoundResourceException $e) {
            $this->fail('Validator should not add a translation resource that does not exist');
        }
    }

    public function getTestValidatorConstraintProvider()
    {
        // Email, form is valid, nb global error, nb email error
        return array(
            array('', false, 0, 1),
            array('not an email', false, 0, 1),
            array('email@sample.com', true, 0, 0),
        );
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
        $app['translator'] = $app->extend('translator', function ($translator, $app) {
            $translator->addResource('array', array('This value should not be blank.' => 'Pas vide'), 'fr', 'validators');

            return $translator;
        });

        if ($registerValidatorFirst) {
            $app['validator'];
        }

        $this->assertEquals('Pas vide', $app['translator']->trans('This value should not be blank.', array(), 'validators', 'fr'));
    }

    public function getAddResourceData()
    {
        return array(array(false), array(true));
    }

    public function testAddResourceAlternate()
    {
        $app = new Application();
        $app['locale'] = 'fr';

        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app->factory($app->extend('translator.resources', function ($resources, $app) {
            $resources = array_merge($resources, array(
                array('array', array('This value should not be blank.' => 'Pas vide'), 'fr', 'validators'),
            ));

            return $resources;
        }));

        $app['validator'];

        $this->assertEquals('Pas vide', $app['translator']->trans('This value should not be blank.', array(), 'validators', 'fr'));
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
