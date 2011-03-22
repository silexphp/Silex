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
 * Application test cases.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testFluidInterface()
    {
        $application = new Application();

        $returnValue = $application->match('/foo', function() {});
        $this->assertSame($application, $returnValue, '->match() should return $this');

        $returnValue = $application->get('/foo', function() {});
        $this->assertSame($application, $returnValue, '->get() should return $this');

        $returnValue = $application->post('/foo', function() {});
        $this->assertSame($application, $returnValue, '->post() should return $this');

        $returnValue = $application->put('/foo', function() {});
        $this->assertSame($application, $returnValue, '->put() should return $this');

        $returnValue = $application->delete('/foo', function() {});
        $this->assertSame($application, $returnValue, '->delete() should return $this');

        $returnValue = $application->before(function() {});
        $this->assertSame($application, $returnValue, '->before() should return $this');

        $returnValue = $application->after(function() {});
        $this->assertSame($application, $returnValue, '->after() should return $this');

        $returnValue = $application->error(function() {});
        $this->assertSame($application, $returnValue, '->error() should return $this');
    }

    public function testGetRequest()
    {
        $application = new Application();

        $application->get('/', function() {
            return 'root';
        });

        $request = Request::create('/');

        $application->handle($request);

        $this->assertEquals($request, $application->getRequest());
    }
}
