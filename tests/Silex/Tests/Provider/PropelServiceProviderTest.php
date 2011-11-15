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
use Silex\Provider\PropelServiceProvider;

/**
 * PropelProvider test cases.
 *
 * Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PropelServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/propel/runtime/lib')) {
            $this->markTestSkipped('Propel submodules were not installed.');
        }
    }

    public function testRegisterWithProperties()
    {
        $app = new Application();

        $app->register(new PropelServiceProvider(), array(
           'propel.path'=>__DIR__.'/../../../../vendor/propel/runtime/lib',
           'propel.config_file'=>__DIR__.'/PropelTestData/build/conf/myproject-conf.php',
           'propel.model_path'=>__DIR__.'/PropelTestData/build/classes',
        ));

        $this->assertTrue(class_exists('Propel'));
        
    }
    

    public function testRegisterDefaults()
    {
        $current = getcwd();
        chdir(__DIR__.'/PropelTestData');
        
        $app = new Application();
        $app->register(new PropelServiceProvider());
        
        $this->assertTrue(class_exists('Propel'));
        
        chdir($current);
    }

    
    public function testRegisterInternalAutoload()
    {
        $app = new Application();

        $app->register(new PropelServiceProvider(), array(
           'propel.path' => __DIR__.'/../../../../vendor/propel/runtime/lib',
           'propel.config_file' => __DIR__.'/PropelTestData/build/conf/myproject-conf.php',
           'propel.model_path' => __DIR__.'/PropelTestData/build/classes',
           'propel.internal_autoload' => true,
        ));

        $this->assertTrue(class_exists('Propel'), 'Propel class doesn\'t exist');
        $this->assertGreaterThan(strpos(get_include_path(), $app['propel.model_path']), 1);
    }
    
}
