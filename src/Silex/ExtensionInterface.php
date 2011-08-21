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

/**
 * Interface that must implement all Silex extensions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface ExtensionInterface
{
    /**
     * Registers an extension.
     *
     * @param Application $app An Application instance
     */
    function register(Application $app);
}
