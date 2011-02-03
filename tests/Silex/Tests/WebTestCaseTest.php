<?php

namespace Silex\Tests;

use Silex\Framework;
use Silex\WebTestCase;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Functional test cases.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class WebTestCaseTest extends WebTestCase
{
    public function createApp()
    {
        $app = new Framework();
        $app->get('/hello', function() {
            return 'world';
        });

        return $app;
    }

    public function testGetHello()
    {
        $client = $this->createClient();

        $client->request('GET', '/hello');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('world', $response->getContent());
    }
}
