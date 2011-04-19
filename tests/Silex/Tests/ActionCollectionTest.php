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

use Silex\ActionCollection;

/**
 * Action collection test cases.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ActionCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testActionCollectionInvoke()
    {
        $collection = new ActionCollection();
        $self       = $this;

        $action1Called = false;
        $action2Called = false;

        $collection->add(function($arg1, $arg2) use($self, &$action1Called) {
            $action1Called = true;
            $self->assertEquals('var1', $arg1);
            $self->assertEquals('variable_2', $arg2);
        });
        $collection->add(function($arg1, $arg2) use($self, &$action2Called) {
            $action2Called = true;
            $self->assertEquals('var1', $arg1);
            $self->assertEquals('variable_2', $arg2);
        });

        $this->assertNull(call_user_func($collection, 'var1', 'variable_2'));

        $this->assertTrue($action1Called);
        $this->assertTrue($action2Called);
    }

    public function testFirstReturnStopsActionsChain()
    {
        $collection = new ActionCollection();
        $self       = $this;

        $action1Called = false;
        $action2Called = false;
        $action3Called = false;

        $collection->add(function($arg1, $arg2) use($self, &$action1Called) {
            $action1Called = true;
            $self->assertEquals('var1', $arg1);
            $self->assertEquals('variable_2', $arg2);
        });
        $collection->add(function($arg1, $arg2) use($self, &$action2Called) {
            $action2Called = true;
            $self->assertEquals('var1', $arg1);
            $self->assertEquals('variable_2', $arg2);

            return 'Hello, world';
        });
        $collection->add(function($arg1, $arg2) use($self, &$action3Called) {
            $action3Called = true;
            $self->assertEquals('var1', $arg1);
            $self->assertEquals('variable_2', $arg2);
        });

        $this->assertEquals('Hello, world', call_user_func($collection, 'var1', 'variable_2'));

        $this->assertTrue($action1Called);
        $this->assertTrue($action2Called);
        $this->assertFalse($action3Called);
    }
}
