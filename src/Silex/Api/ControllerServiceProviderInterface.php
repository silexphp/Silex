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

use Pimple\ServiceProviderInterface;

/**
 * Interface for controller service providers.
 */
interface ControllerServiceProviderInterface extends ControllerProviderInterface, ServiceProviderInterface
{
}
