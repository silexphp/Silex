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
use Silex\Provider\CsrfServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

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

        $app->extend('form.types', function ($extensions) {
            $extensions[] = new DummyFormType();

            return $extensions;
        });

        $form = $app['form.factory']->createBuilder(class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Symfony\Component\Form\Extension\Core\Type\FormType' : 'form', array())
            ->add('dummy', class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Silex\Tests\Provider\DummyFormType' : 'dummy')
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    public function testFormServiceProviderWillLoadTypeExtensions()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.type.extensions', function ($extensions) {
            $extensions[] = new DummyFormTypeExtension();

            return $extensions;
        });

        $form = $app['form.factory']->createBuilder(class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Symfony\Component\Form\Extension\Core\Type\FormType' : 'form', array())
            ->add('file', class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Symfony\Component\Form\Extension\Core\Type\FileType' : 'file', array('image_path' => 'webPath'))
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    public function testFormServiceProviderWillLoadTypeGuessers()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.type.guessers', function ($guessers) {
            $guessers[] = new FormTypeGuesserChain(array());

            return $guessers;
        });

        $this->assertInstanceOf('Symfony\Component\Form\FormFactory', $app['form.factory']);
    }

    public function testFormServiceProviderWillUseTranslatorIfAvailable()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app['translator.domains'] = array(
            'messages' => array(
                'de' => array(
                    'The CSRF token is invalid. Please try to resubmit the form.' => 'German translation',
                ),
            ),
        );
        $app['locale'] = 'de';

        $app['csrf.token_manager'] = function () {
            return $this->getMock('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface');
        };

        $form = $app['form.factory']->createBuilder(class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Symfony\Component\Form\Extension\Core\Type\FormType' : 'form', array())
            ->getForm();

        $form->handleRequest($req = Request::create('/', 'POST', array('form' => array(
            '_token' => 'the wrong token',
        ))));

        $this->assertFalse($form->isValid());
        $r = new \ReflectionMethod($form, 'getErrors');
        if (!$r->getNumberOfParameters()) {
            $this->assertContains('ERROR: German translation', $form->getErrorsAsString());
        } else {
            // as of 2.5
            $this->assertContains('ERROR: German translation', (string) $form->getErrors(true, false));
        }
    }

    public function testFormServiceProviderWillNotAddNonexistentTranslationFiles()
    {
        $app = new Application(array(
            'locale' => 'nonexistent',
        ));

        $app->register(new FormServiceProvider());
        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider(), array(
            'locale_fallbacks' => array(),
        ));

        $app['form.factory'];
        $translator = $app['translator'];

        try {
            $translator->trans('test');
        } catch (NotFoundResourceException $e) {
            $this->fail('Form factory should not add a translation resource that does not exist');
        }
    }

    public function testFormCsrf()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new CsrfServiceProvider());
        $app['session.test'] = true;

        $form = $app['form.factory']->createBuilder(class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Symfony\Component\Form\Extension\Core\Type\FormType' : 'form', array())->getForm();

        $this->assertTrue(isset($form->createView()['_token']));
    }
}

if (class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType')) {
    class DummyFormType extends AbstractType
    {
    }
} else {
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
}

if (method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')) {
    class DummyFormTypeExtension extends AbstractTypeExtension
    {
        public function getExtendedType()
        {
            return class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Symfony\Component\Form\Extension\Core\Type\FileType' : 'file';
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefined(array('image_path'));
        }
    }
} else {
    class DummyFormTypeExtension extends AbstractTypeExtension
    {
        public function getExtendedType()
        {
            return class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType') ? 'Symfony\Component\Form\Extension\Core\Type\FileType' : 'file';
        }

        public function setDefaultOptions(OptionsResolverInterface $resolver)
        {
            if (!method_exists($resolver, 'setDefined')) {
                $resolver->setOptional(array('image_path'));
            } else {
                $resolver->setDefined(array('image_path'));
            }
        }
    }
}
