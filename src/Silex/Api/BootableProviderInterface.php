<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Api;

use Pimple\Container;

/**
 * Interface that must implement all Silex service providers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface BootableProviderInterface
{
    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param Container $app
     */
    public function boot(Container $app);
}
