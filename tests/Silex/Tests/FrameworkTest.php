<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests;

use Silex\Framework;
use Symfony\Component\HttpFoundation\Request;

/**
 * Framework test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class FrameworkTest extends \PHPUnit_Framework_TestCase
{
    public function testFluidInterface()
    {
        $framework = new Framework();

        $returnValue = $framework->match('/foo', function() {});
        $this->assertSame($framework, $returnValue, '->match() should return $this');

        $returnValue = $framework->get('/foo', function() {});
        $this->assertSame($framework, $returnValue, '->get() should return $this');

        $returnValue = $framework->post('/foo', function() {});
        $this->assertSame($framework, $returnValue, '->post() should return $this');

        $returnValue = $framework->put('/foo', function() {});
        $this->assertSame($framework, $returnValue, '->put() should return $this');

        $returnValue = $framework->delete('/foo', function() {});
        $this->assertSame($framework, $returnValue, '->delete() should return $this');

        $returnValue = $framework->before(function() {});
        $this->assertSame($framework, $returnValue, '->before() should return $this');

        $returnValue = $framework->after(function() {});
        $this->assertSame($framework, $returnValue, '->after() should return $this');

        $returnValue = $framework->error(function() {});
        $this->assertSame($framework, $returnValue, '->error() should return $this');
    }

    public function testGetRequest()
    {
        $framework = new Framework();

        $framework->get('/', function() {
            return 'root';
        });

        $request = Request::create('/');

        $framework->handle($request);

        $this->assertEquals($request, $framework->getRequest());
    }
}
