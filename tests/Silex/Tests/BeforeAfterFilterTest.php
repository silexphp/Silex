<?php

namespace Silex\Tests;

use Silex\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Error handler test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class BeforeAfterFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testBeforeAndAfterFilter()
    {
        $i = 0;

        $test = $this;

        $framework = new Framework();

        $framework->before(function() use(&$i, $test) {
            $test->assertEquals(0, $i);
            $i++;
        });

        $framework->match('/foo', function() use(&$i, $test) {
            $test->assertEquals(1, $i);
            $i++;
        });

        $framework->after(function() use(&$i, $test) {
            $test->assertEquals(2, $i);
            $i++;
        });

        $request = Request::create('/foo');
        $framework->handle($request);

        $this->assertEquals(3, $i);
    }

    public function testAfterFilterWithResponseObject()
    {
        $i = 0;

        $framework = new Framework();

        $framework->match('/foo', function() use (&$i) {
            $i++;
            return new Response('foo');
        });

        $framework->after(function() use(&$i) {
            $i++;
        });

        $request = Request::create('/foo');
        $framework->handle($request);

        $this->assertEquals(2, $i);
    }

    public function testMultipleFilters()
    {
        $i = 0;

        $test = $this;

        $framework = new Framework();

        $framework->before(function() use(&$i, $test) {
            $test->assertEquals(0, $i);
            $i++;
        });

        $framework->before(function() use(&$i, $test) {
            $test->assertEquals(1, $i);
            $i++;
        });

        $framework->match('/foo', function() use(&$i, $test) {
            $test->assertEquals(2, $i);
            $i++;
        });

        $framework->after(function() use(&$i, $test) {
            $test->assertEquals(3, $i);
            $i++;
        });

        $framework->after(function() use(&$i, $test) {
            $test->assertEquals(4, $i);
            $i++;
        });

        $request = Request::create('/foo');
        $framework->handle($request);

        $this->assertEquals(5, $i);
    }
}
