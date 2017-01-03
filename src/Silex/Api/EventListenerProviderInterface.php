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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pimple\Container;

/**
 * Interface for event listener providers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface EventListenerProviderInterface
{
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher);
}
