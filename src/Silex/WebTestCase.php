<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * WebTestCase is the base class for functional tests.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
abstract class WebTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * HttpKernelInterface instance.
     *
     * @var HttpKernelInterface
     */
    protected $app;

    /**
     * PHPUnit setUp for setting up the application.
     *
     * Note: Child classes that define a setUp method must call
     * parent::setUp().
     */
    public function setUp()
    {
        $this->app = $this->createApplication();
    }

    /**
     * Creates the application.
     *
     * @return HttpKernelInterface
     */
    abstract public function createApplication();

    /**
     * Creates a Client.
     *
     * @param array $server Server parameters
     *
     * @return Client A Client instance
     */
    public function createClient(array $server = array())
    {
        if (!class_exists('Symfony\Component\BrowserKit\Client')) {
            throw new \LogicException('Component "symfony/browser-kit" is required by WebTestCase.'.PHP_EOL.'Run composer require symfony/browser-kit');
        }

        return new Client($this->app, $server);
    }
}
