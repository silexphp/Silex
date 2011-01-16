<?php

namespace Silex\Tests;

use Silex\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Error handler test cases.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
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

        $request = Request::create('http://test.com/foo');
        $framework->handle($request);

        $test->assertEquals(3, $i);
    }
}
