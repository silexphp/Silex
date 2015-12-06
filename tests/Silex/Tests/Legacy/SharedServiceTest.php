<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Legacy;

use Silex\Legacy\SharedService;

class SharedServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCallable()
    {
        $callable = function () {};
        $sharedService = new SharedService($callable);
        $this->assertSame($callable, $sharedService->getCallable());
    }
}