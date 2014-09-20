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

use Silex\Application;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testFormFactoryServiceIsFormFactory()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $this->assertInstanceOf('Symfony\Component\Form\FormFactory', $app['form.factory']);
    }

    public function testFormServiceProviderWillLoadTypes()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app['form.types'] = $app->share($app->extend('form.types', function ($extensions) {
            $extensions[] = new DummyFormType();

            return $extensions;
        }));

        $form = $app['form.factory']->createBuilder('form', array())
            ->add('dummy', 'dummy')
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    public function testFormServiceProviderWillLoadTypeExtensions()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app['form.type.extensions'] = $app->share($app->extend('form.type.extensions', function ($extensions) {
            $extensions[] = new DummyFormTypeExtension();

            return $extensions;
        }));

        $form = $app['form.factory']->createBuilder('form', array())
            ->add('file', 'file', array('image_path' => 'webPath'))
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    public function testFormServiceProviderWillLoadTypeGuessers()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app['form.type.guessers'] = $app->share($app->extend('form.type.guessers', function ($guessers) {
            $guessers[] = new FormTypeGuesserChain(array());

            return $guessers;
        }));

        $this->assertInstanceOf('Symfony\Component\Form\FormFactory', $app['form.factory']);
    }

    public function testFormServiceProviderWillUseTranslatorIfAvailable()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app['translator.domains'] = array(
            'messages' => array(
                'de' => array (
                    'The CSRF token is invalid. Please try to resubmit the form.' => 'German translation',
                ),
            ),
        );
        $app['locale'] = 'de';

        $app['form.csrf_provider'] = $app->share(function () {
            return new FakeCsrfProvider();
        });

        $form = $app['form.factory']->createBuilder('form', array())
            ->getForm();

        $form->handleRequest($req = Request::create('/', 'POST', array('form' => array(
            '_token' => 'the wrong token',
        ))));

        $this->assertFalse($form->isValid());
        $this->assertContains('ERROR: German translation', $form->getErrorsAsString());
    }
}

class DummyFormType extends AbstractType
{
    /**
     * @return string The name of this type
     */
    public function getName()
    {
        return 'dummy';
    }
}

class DummyFormTypeExtension extends AbstractTypeExtension
{
    public function getExtendedType()
    {
        return 'file';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('image_path'));
    }
}

class FakeCsrfProvider implements CsrfProviderInterface
{
    public function generateCsrfToken($intention)
    {
        return $intention.'123';
    }

    public function isCsrfTokenValid($intention, $token)
    {
        return $token === $this->generateCsrfToken($intention);
    }
}
