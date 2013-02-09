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
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Router test cases, using the ServiceControllerResolver
 */
class ServiceControllerResolverRouterTest extends RouterTest
{
    public function testServiceNameControllerSyntax()
    {
        $app = $this->createApplication();

        $app['service_name'] = function () {
            return new MyController;
        };

        $app->get('/bar', 'service_name:getBar');

        $this->checkRouteResponse($app, '/bar', 'bar');
    }

    protected function createApplication()
    {
        $app = parent::createApplication();
        $app->register(new ServiceControllerServiceProvider());
        return $app;
    }
}
