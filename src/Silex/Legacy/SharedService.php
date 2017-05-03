<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Legacy;

/**
 * @internal
 */
class SharedService
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @param $callable
     */
    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    /**
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }
}
