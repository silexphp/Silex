<?php

/*
* This file is an extension of the Silex framework.
*
* (c) Fabien Potencier <fabien@symfony.com>
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Silex\Tests;

use Silex\Application;
use Silex\Extension\ValidatorExtension;

/**
* ValidatorExtension test cases.
*
* @author Masao Maeda <brt.river@gmail.com>
*/
class ValidatorExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/Symfony/Component/Validator')) {
            $this->markTestSkipped('Symfony/Component/Validator submodule was not installed.');
        }
    }
    public function testRegister()
    {
        $app = new Application();
        $app->register(new ValidatorExtension());
        $this->assertInstanceOf('Symfony\Component\Validator\Validator', $app['validator']);
    }
    public function testCallValidateValue()
    {
        $app = new Application();
        $app->register(new ValidatorExtension());
        // see more constraints: http://api.symfony.com/2.0/Symfony/Component/Validator/Constraints.html
        $url = 'htt://symfony.com';
        $violations = $app['validator']->validateValue($url, new \Symfony\Component\Validator\Constraints\Url());
        $this->assertEquals(1, $violations->count());
        foreach ($violations as $violation) {
            $this->assertEquals('This value is not a valid URL', $violation->getMessage());
        }
    }
}