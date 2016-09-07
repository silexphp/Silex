<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Application;

use Monolog\Logger;

/**
 * Monolog trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait MonologTrait
{
    /**
     * Adds a log record.
     *
     * @param string $message The log message
     * @param array  $context The log context
     * @param int    $level   The logging level
     *
     * @return bool Whether the record has been processed
     */
    public function log($message, array $context = [], $level = Logger::INFO)
    {
        return $this['monolog']->addRecord($level, $message, $context);
    }
}
