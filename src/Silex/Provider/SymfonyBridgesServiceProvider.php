<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Symfony bridges Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SymfonyBridgesServiceProvider implements ServiceProviderInterface
{
    // BC: this class needs to be removed before 1.0
    public function __construct()
    {
        throw new \RuntimeException('You tried to create a SymfonyBridgesServiceProvider. However, it has been removed from Silex. Make sure that the Symfony bridge you want to use is autoloadable, and it will get loaded automatically. You should remove the creation of the SymfonyBridgesServiceProvider, as it is no longer needed.');
    }

    public function register(Application $app)
    {
    }

    public function boot(Application $app)
    {
    }
}
