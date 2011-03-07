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

        $app->match('/hello', function() {
            return 'world';
        });

        $app->match('/html', function() {
            return '<h1>title</h1>';
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

    public function testCrawlerFilter()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/html');
        $this->assertEquals('title', $crawler->filter('h1')->text());
    }
}
