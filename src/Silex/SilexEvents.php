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
 * The Silex events.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
final class SilexEvents
{
    const BEFORE = 'silex.before';
    const AFTER  = 'silex.after';
    const FINISH = 'silex.finish';
    const ERROR  = 'silex.error';
}
