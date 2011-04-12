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
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\HttpFoundation\Request;
use Silex\Tests\Fixtures\Entity\Author;
use Silex\Tests\Fixtures\Entity\Reader;

/**
 * ValidatorExtension test cases.
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
        $loader = new UniversalClassLoader();
        $loader->registerNamespace('Silex', array(__DIR__.'/../../../', __DIR__.'/../../../../src'));
        $loader->register();
        $this->app = new Application();
    }
    public function testRegister()
    {
        $app = $this->app;
        $app->register(new ValidationExtension());

        $this->assertInstanceOf('Symfony\Component\Validator\Validator', $app['validator']);
    }
    public function testPropertyValidate()
    {
        $app = $this->app;
        $app->register(new ValidationExtension());
        $request = Request::create('/', 'POST', array('firstName' => 'foo', 'lastName' => ''));

        $author = new Author;
        $author->setFirstName($request->get('firstName'));
        $author->setLastName($request->get('lastName'));
        $rs = $app['validator']->validate($author);
        $this->assertEquals(1, count($rs));
        $this->assertRegExp('/Last Name should not be blank/', $rs->__toString());
    }
    public function testAnnotationValidate()
    {
        $app = $this->app;
        $app['validation.use_annotation'] = true;
        $app->register(new ValidationExtension());
        $request = Request::create('/', 'POST', array('firstName' => 'foo', 'lastName' => ''));

        $reader = new Reader;
        $reader->setFirstName($request->get('firstName'));
        $reader->setLastName($request->get('lastName'));
        $rs = $app['validator']->validate($reader);
        $this->assertEquals(1, count($rs));
        $this->assertRegExp('/Last Name should not be blank/', $rs->__toString());
    }
}