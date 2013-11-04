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

/**
 * Interface that all Silex service providers must implement.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface ServiceProviderInterface
{
    /**
     * Registers services on the given Pimple container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Pimple $app A Pimple instance
     */
    public function register(\Pimple $app);
}
