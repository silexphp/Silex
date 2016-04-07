<?php

namespace Silex\Tests\Route;

use Silex\Application;
use Silex\WebTestCase;

/**
 * Route with condition (expression) test cases.
 *
 * @author SpacePossum
 */
class ConditionalRouteTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public function createApplication()
    {
        $app = new Application();
        $app['session.test'] = true;
        $app['debug'] = true;
        $app->get('/', function () { return 'test'; })->getRoute()->setCondition('request.isSecure() == false');

        return $app;
    }

    public function testTest()
    {
        $client = $this->createClient();
        $client->request('GET', '/');
        $this->assertSame('test', $client->getResponse()->getContent());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
