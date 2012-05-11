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
use Silex\Provider\ValidatorServiceProvider;

/**
 * ValidatorServiceProvider
 *
 * Javier Lopez <f12loalf@gmail.com>
 */
class ValidatorServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/symfony/validator')) {
            $this->markTestSkipped('Validator dependency was not installed.');
        }
    }

    public function testRegister()
    {
        $app = new Application();

        $app->register(new ValidatorServiceProvider());

        return $app;
    }

    /**
     * @depends testRegister
     */
    public function testValidatorServiceIsAValidator($app)
    {
        $this->assertInstanceOf('Symfony\Component\Validator\Validator', $app['validator']);
    }

    /**
     * @depends testRegister
     */
    public function testValidatorServiceWithFormServiceDisabled($app)
    {
        if (!is_dir(__DIR__ . '/../../../../vendor/symfony/form')) {
            $this->markTestSkipped('Form submodule was not installed.');
        }

        $metadatas = $app['validator']->getMetadataFactory()->getClassMetadata('Symfony\Component\Form\Form');

        $this->assertEquals(0, count($metadatas->constraints));
    }

    /**
     * @depends testRegister
     */
    public function testValidatorServiceWithFormServiceEnabled($app)
    {
        if (!is_dir(__DIR__ . '/../../../../vendor/symfony/form')) {
            $this->markTestSkipped('Form submodule was not installed.');
        }

        $app->register(new ValidatorServiceProvider());

        $app['form.factory'] = true;

        $metadatas = $app['validator']->getMetadataFactory()->getClassMetadata('Symfony\Component\Form\Form');

        $this->assertEquals(1, count($metadatas->constraints));
    }
}
