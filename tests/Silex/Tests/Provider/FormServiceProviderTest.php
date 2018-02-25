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

class FormServiceProviderTest extends TestCase
{
    public function testFormFactoryServiceIsFormFactory()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $this->assertInstanceOf('Symfony\Component\Form\FormFactory', $app['form.factory']);
    }

    public function testFormRegistryServiceIsFormRegistry()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $this->assertInstanceOf('Symfony\Component\Form\FormRegistry', $app['form.registry']);
    }

    public function testFormServiceProviderWillLoadTypes()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.types', function ($extensions) {
            $extensions[] = new DummyFormType();

            return $extensions;
        });

        $form = $app['form.factory']->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])
            ->add('dummy', 'Silex\Tests\Provider\DummyFormType')
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    public function testFormServiceProviderWillLoadTypesServices()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app['dummy'] = function () {
            return new DummyFormType();
        };
        $app->extend('form.types', function ($extensions) {
            $extensions[] = 'dummy';

            return $extensions;
        });

        $form = $app['form.factory']
            ->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])
            ->add('dummy', 'dummy')
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid form type. The silex service "dummy" does not exist.
     */
    public function testNonExistentTypeService()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.types', function ($extensions) {
            $extensions[] = 'dummy';

            return $extensions;
        });

        $app['form.factory']
            ->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])
            ->add('dummy', 'dummy')
            ->getForm();
    }

    public function testFormServiceProviderWillLoadTypeExtensions()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.type.extensions', function ($extensions) {
            $extensions[] = new DummyFormTypeExtension();

            return $extensions;
        });

        $form = $app['form.factory']->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])
            ->add('file', 'Symfony\Component\Form\Extension\Core\Type\FileType', ['image_path' => 'webPath'])
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    public function testFormServiceProviderWillLoadTypeExtensionsServices()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app['dummy.form.type.extension'] = function () {
            return new DummyFormTypeExtension();
        };
        $app->extend('form.type.extensions', function ($extensions) {
            $extensions[] = 'dummy.form.type.extension';

            return $extensions;
        });

        $form = $app['form.factory']
            ->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])
            ->add('file', 'Symfony\Component\Form\Extension\Core\Type\FileType', ['image_path' => 'webPath'])
            ->getForm();

        $this->assertInstanceOf('Symfony\Component\Form\Form', $form);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid form type extension. The silex service "dummy.form.type.extension" does not exist.
     */
    public function testNonExistentTypeExtensionService()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.type.extensions', function ($extensions) {
            $extensions[] = 'dummy.form.type.extension';

            return $extensions;
        });

        $app['form.factory']
            ->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])
            ->add('dummy', 'dummy.form.type')
            ->getForm();
    }

    public function testFormServiceProviderWillLoadTypeGuessers()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.type.guessers', function ($guessers) {
            $guessers[] = new FormTypeGuesserChain([]);

            return $guessers;
        });

        $this->assertInstanceOf('Symfony\Component\Form\FormFactory', $app['form.factory']);
    }

    public function testFormServiceProviderWillLoadTypeGuessersServices()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app['dummy.form.type.guesser'] = function () {
            return new FormTypeGuesserChain([]);
        };
        $app->extend('form.type.guessers', function ($guessers) {
            $guessers[] = 'dummy.form.type.guesser';

            return $guessers;
        });

        $this->assertInstanceOf('Symfony\Component\Form\FormFactory', $app['form.factory']);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid form type guesser. The silex service "dummy.form.type.guesser" does not exist.
     */
    public function testNonExistentTypeGuesserService()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app->extend('form.type.guessers', function ($extensions) {
            $extensions[] = 'dummy.form.type.guesser';

            return $extensions;
        });

        $factory = $app['form.factory'];
    }

    public function testFormServiceProviderWillUseTranslatorIfAvailable()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app['translator.domains'] = [
            'messages' => [
                'de' => [
                    'The CSRF token is invalid. Please try to resubmit the form.' => 'German translation',
                ],
            ],
        ];
        $app['locale'] = 'de';

        $app['csrf.token_manager'] = function () {
            return $this->getMockBuilder('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface')->getMock();
        };

        $form = $app['form.factory']->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])
            ->getForm();

        $form->handleRequest($req = Request::create('/', 'POST', ['form' => [
            '_token' => 'the wrong token',
        ]]));

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
        $app = new Application([
            'locale' => 'nonexistent',
        ]);

        $app->register(new FormServiceProvider());
        $app->register(new ValidatorServiceProvider());
        $app->register(new TranslationServiceProvider(), [
            'locale_fallbacks' => [],
        ]);

        $app['form.factory'];
        $translator = $app['translator'];

        try {
            $translator->trans('test');
            $this->addToAssertionCount(1);
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

        $form = $app['form.factory']->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])->getForm();

        $this->assertTrue(isset($form->createView()['_token']));
    }

    public function testUserExtensionCanConfigureDefaultExtensions()
    {
        $app = new Application();
        $app->register(new FormServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new CsrfServiceProvider());
        $app['session.test'] = true;

        $app->extend('form.type.extensions', function ($extensions) {
            $extensions[] = new FormServiceProviderTest\DisableCsrfExtension();

            return $extensions;
        });
        $form = $app['form.factory']->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', [])->getForm();

        $this->assertFalse($form->getConfig()->getOption('csrf_protection'));
    }
}

if (!class_exists('Symfony\Component\Form\Deprecated\FormEvents')) {
    class DummyFormType extends AbstractType
    {
    }
} else {
    // FormTypeInterface::getName() is needed by the form component 2.8.x
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
            return 'Symfony\Component\Form\Extension\Core\Type\FileType';
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefined(['image_path']);
        }
    }
} else {
    class DummyFormTypeExtension extends AbstractTypeExtension
    {
        public function getExtendedType()
        {
            return 'Symfony\Component\Form\Extension\Core\Type\FileType';
        }

        public function setDefaultOptions(OptionsResolverInterface $resolver)
        {
            if (!method_exists($resolver, 'setDefined')) {
                $resolver->setOptional(['image_path']);
            } else {
                $resolver->setDefined(['image_path']);
            }
        }
    }
}
