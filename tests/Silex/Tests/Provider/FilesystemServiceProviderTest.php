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
use Silex\Provider\FilesystemServiceProvider;

/**
 * FilesystemServiceProvider test cases.
 *
 * @author Romain Neutron <imprec@gmail.com>
 */
class FilesystemServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();
        $app->register(new FilesystemServiceProvider());

        $this->assertInstanceOf('Symfony\Component\Filesystem\Filesystem', $app['filesystem']);
    }

    public function testTouch()
    {
        $app = new Application();
        $app->register(new FilesystemServiceProvider());

        $file = tempnam(sys_get_temp_dir(), 'test');
        unlink($file);

        $this->assertFalse(file_exists($file));

        $app['filesystem']->touch($file);

        $this->assertTrue(file_exists($file));

        unlink($file);
    }
}
