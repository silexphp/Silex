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
use Silex\Extension\ValidationExtension;

/**
* ValidationExtension test cases.
*
* @author Masao Maeda <brt.river@gmail.com>
*/
class ValidationExtensionTest extends \PHPUnit_Framework_TestCase
{
    private $app;
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/Symfony/Component/Validator')) {
            $this->markTestSkipped('Symfony/Component/Validator submodule was not installed.');
        }
        $this->app = new Application();
    }
    public function testRegister()
    {
        $app = $this->app;
        $app->register(new ValidationExtension());
        $this->assertInstanceOf('Symfony\Component\Validator\Validator', $app['validator']);
    }
    public function testCallValidateValue()
    {
        $app = $this->app;
        $app->register(new ValidationExtension());
        // see more constraints: http://api.symfony.com/2.0/Symfony/Component/Validator/Constraints.html
        $url = 'htt://symfony.com';
        $vaiolationList = $app['validator']->validateValue($url, new \Symfony\Component\Validator\Constraints\Url());
        $this->assertEquals(1, $vaiolationList->count());
        foreach ($vaiolationList as $vaiolation) {
            $this->assertEquals($vaiolation->getMessage(), 'This value is not a valid URL');
        }
    }
}