<?php

namespace Silex;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Test\WebTestCase as BaseWebTestCase;

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * WebTestCase is the base class for functional tests.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    protected $app;

    /**
     * PHPUnit setUp for setting up the application.
     *
     * Note: Child classes that define a setUp method must call
     * parent::setUp().
     */
    public function setUp()
    {
        $this->app = $this->createApp();
    }

    /**
     * Create the application (HttpKernel, most likely Framework).
     *
     * @return Symfony\Component\HttpKernel\HttpKernel
     */
    abstract public function createApp();

    /**
     * Creates a Client.
     *
     * @param array   $options An array of options to pass to the createKernel class
     * @param array   $server  An array of server parameters
     *
     * @return Client A Client instance
     */
    public function createClient(array $options = array(), array $server = array())
    {
        return new Client($this->app);
    }
}
