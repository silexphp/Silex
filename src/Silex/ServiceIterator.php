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

use Iterator;
use Pimple\Container;

/**
 * Lazy service iterator supporting both objects and service IDs resolved lazily.
 *
 * @internal
 * @author Pascal Luna <skalpa@zetareticuli.org>
 * @author Haralan Dobrev <harry@hkdobrev.com>
 */
final class ServiceIterator implements Iterator
{
    /** @var Container */
    private $container;

    /** @var array of both service IDs and objects */
    private $serviceItems;

    public function __construct(Container $container, array $serviceItems)
    {
        $this->container = $container;
        $this->serviceItems = $serviceItems;
    }

    public function rewind()
    {
        \reset($this->serviceItems);
    }

    public function current()
    {
        $item = \current($this->serviceItems);
        if (\is_string($item) && \isset($this->container[$item])) {
            return $this->container[$item];
        }

        return $item;
    }

    public function key()
    {
        return \current($this->serviceItems);
    }

    public function next()
    {
        \next($this->serviceItems);
    }

    public function valid()
    {
        return null !== \key($this->serviceItems);
    }
}
