<?php

namespace Silex\Tests;

use Silex\Framework;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Framework test cases.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class FrameworkTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $framework = Framework::create();
        $this->assertInstanceOf('Silex\Framework', $framework, "Framework::create() must return instance of Framework");
    }

    public function testFluidInterface()
    {
        $framework = Framework::create();

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

        $returnValue = $framework->error(function() {});
        $this->assertSame($framework, $returnValue, '->error() should return $this');
    }
}
