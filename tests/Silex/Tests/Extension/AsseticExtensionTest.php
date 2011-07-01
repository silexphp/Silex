<?php

/*
* This file is part of the Silex framework.
*
* (c) Fabien Potencier <fabien@symfony.com>
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Silex\Tests\Extension;

use Silex\Application;
use Silex\Extension\AsseticExtension;

use Symfony\Component\HttpFoundation\Request;

/**
* AsseticExtensionTest test cases.
*
* @author Sven Eisenschmidt <sven.eisenschmidt@gmail.com>
*/
class AsseticExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__ . '/../../../../vendor/assetic/src')) {
            $this->markTestSkipped('Assetic was not installed.');
        }
    }
    
    public function testEverythingLoaded()
    {
        $app = new Application();
        $app->register(new AsseticExtension(), array(
            'assetic.class_path' => __DIR__ . '/../../../../vendor/assetic/src',
            'assetic.options' => array(
                'path_to_web' => sys_get_temp_dir()
            )
        ));
        $app->get('/', function () use ($app) {
            return 'AsseticExtensionTest';
        });
        
        $request = Request::create('/');
        $response = $app->handle($request);
        
        $this->assertInstanceOf('Assetic\Factory\AssetFactory', $app['assetic']);
        $this->assertInstanceOf('Assetic\AssetManager', $app['assetic.asset_manager']);
        $this->assertInstanceOf('Assetic\FilterManager', $app['assetic.filter_manager']);
        $this->assertInstanceOf('Assetic\AssetWriter', $app['assetic.asset_writer']);
        $this->assertInstanceOf('Assetic\Factory\LazyAssetManager', $app['assetic.lazy_asset_manager']);
    }
    
    public function testFilterFormRegistration()
    {
        $app = new Application();
        $app->register(new AsseticExtension(), array(
            'assetic.class_path' => __DIR__ . '/../../../../vendor/assetic/src',
            'assetic.options' => array(
                'path_to_web' => sys_get_temp_dir()
            ),
            'assetic.filters' => $app->protect(function($fm) {
                $fm->set('test_filter', new \Assetic\Filter\CssMinFilter());
            })
        ));
        $app->get('/', function () use ($app) {
            return 'AsseticExtensionTest';
        });
        
        $request = Request::create('/');
        $response = $app->handle($request);
        
        $this->assertTrue($app['assetic.filter_manager']->has('test_filter'));
        $this->assertInstanceOf('Assetic\Filter\CssMinFilter', $app['assetic.filter_manager']->get('test_filter'));
    }

    public function testAssetFormRegistration()
    {
        $app = new Application();
        $app->register(new AsseticExtension(), array(
            'assetic.class_path' => __DIR__ . '/../../../../vendor/assetic/src',
            'assetic.options' => array(
                'path_to_web' => sys_get_temp_dir()
            ),
            'assetic.assets' => $app->protect(function($am) {
                $asset = new \Assetic\Asset\FileAsset(__FILE__);
                $asset->setTargetUrl(md5(__FILE__));
                
                $am->set('test_asset', $asset);
            })
        ));
        $app->get('/', function () use ($app) {
            return 'AsseticExtensionTest';
        });
        
        $request = Request::create('/');
        $response = $app->handle($request);
        
        $this->assertTrue($app['assetic.asset_manager']->has('test_asset'));
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $app['assetic.asset_manager']->get('test_asset'));
        $this->assertTrue(file_exists(sys_get_temp_dir() . '/' . md5(__FILE__)));
    }
    
    public function tearDown()
    {
        if (file_exists(sys_get_temp_dir() . '/' . md5(__FILE__))) {
            unlink(sys_get_temp_dir() . '/' . md5(__FILE__));
        }
    }
}
