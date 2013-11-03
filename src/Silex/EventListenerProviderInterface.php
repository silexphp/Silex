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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Interface for event listener providers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface EventListenerProviderInterface
{
    public function subscribe(Application $app, EventDispatcherInterface $dispatcher);
}
