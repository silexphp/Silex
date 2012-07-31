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
use Silex\Provider\FormServiceProvider;

/**
 * FormServiceProvider test cases.
 *
 * @author Francesco Levorato <git@flevour.net>
 */
class FormServiceProviderTest extends \PHPUnit_Framework_TestCase
{

    public function testServicesInstantiation()
    {
        $app = new Application();

        $app->register(new FormServiceProvider());

        $app['form.registry'];
        $app['form.factory'];
    }

}
