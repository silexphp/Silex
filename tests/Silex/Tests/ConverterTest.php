<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Converter listener test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $called = array();
        $globalFooConverter = function () use (&$called) { $called[] = 'global_foo'; };
        $globalBarConverter = function () use (&$called) { $called[] = 'global_bar'; };
        $fooConverter = function () use (&$called) { $called[] = 'foo'; };
        $barConverter = function () use (&$called) { $called[] = 'bar'; };

        $app = new Application();
        $app
            ->get('/', function ($foo, $globalBar) { return 'hallo'; })
            ->convert('foo', $fooConverter)
            ->convert('bar', $barConverter)
        ;
        $app['controllers']->convert('globalFoo', $globalFooConverter);
        $app['controllers']->convert('globalBar', $globalBarConverter);

        $request = Request::create('/');
        $app->handle($request);

        $this->assertEquals(array('foo', 'global_bar'), $called);
    }
}
