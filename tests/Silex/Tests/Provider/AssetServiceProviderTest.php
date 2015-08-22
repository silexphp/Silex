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
use Silex\Provider\AssetServiceProvider;

class AssetServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateAssetUrl()
    {
        $app = new Application();
        $app->register(new AssetServiceProvider(), array(
            'assets.version' => 'v1',
            'assets.version_format' => '%s?version=%s',
            'assets.named_packages' => array(
                'css' => array('version' => 'css2', 'base_path' => '/whatever-makes-sense'),
                'images' => array('base_urls' => array('https://img.example.com')),
            ),
        ));

        $this->assertEquals('/foo.png?version=v1', $app['assets.packages']->getUrl('/foo.png'));
        $this->assertEquals('/whatever-makes-sense/foo.css?css2', $app['assets.packages']->getUrl('/foo.css', 'css'));
        $this->assertEquals('https://img.example.com/foo.png', $app['assets.packages']->getUrl('/foo.png', 'images'));
    }
}
