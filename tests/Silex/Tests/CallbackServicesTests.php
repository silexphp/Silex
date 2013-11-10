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
use Symfony\Component\HttpFoundation\Response;

/**
 * Callback as services test cases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CallbackServicesTest extends \PHPUnit_Framework_TestCase
{
    public $called = array();

    public function testCallbacksAsServices()
    {
        $app = new Application();

        $app['service'] = $app->share(function () {
            return new self();
        });

        $app->before('service:beforeApp');
        $app->after('service:afterApp');
        $app->finish('service:finishApp');
        $app->error('service:error');
        $app->on('kernel.request', 'service:onRequest');

        $app
            ->match('/', 'service:controller')
            ->convert('foo', 'service:convert')
            ->before('service:before')
            ->after('service:after')
        ;

        $request = Request::create('/');
        $response = $app->handle($request);
        $app->terminate($request, $response);

        $this->assertEquals(array(
            'CONVERT',
            'BEFORE APP',
            'ON REQUEST',
            'BEFORE',
            'ERROR',
            'AFTER',
            'AFTER APP',
            'FINISH APP',
        ), $app['service']->called);
    }

    public function controller(Application $app)
    {
        return $app->abort(404);
    }

    public function before()
    {
        $this->called[] = 'BEFORE';
    }

    public function after()
    {
        $this->called[] = 'AFTER';
    }

    public function beforeApp()
    {
        $this->called[] = 'BEFORE APP';
    }

    public function afterApp()
    {
        $this->called[] = 'AFTER APP';
    }

    public function finishApp()
    {
        $this->called[] = 'FINISH APP';
    }

    public function error()
    {
        $this->called[] = 'ERROR';
    }

    public function convert()
    {
        $this->called[] = 'CONVERT';
    }

    public function onRequest()
    {
        $this->called[] = 'ON REQUEST';
    }
}
