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

use Pimple\Container;

class CallbackResolver
{
    const SERVICE_PATTERN = "/[A-Za-z0-9\._\-]+:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/";

    private $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Returns true if the string is a valid service method representation.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isValid($name)
    {
        return is_string($name) && (preg_match(static::SERVICE_PATTERN, $name) || isset($this->app[$name]));
    }

    /**
     * Returns a callable given its string representation.
     *
     * @param string $name
     *
     * @return callable
     *
     * @throws \InvalidArgumentException In case the method does not exist.
     */
    public function convertCallback($name)
    {
        if (preg_match(static::SERVICE_PATTERN, $name)) {
            list($service, $method) = explode(':', $name, 2);
            $callback = array($this->app[$service], $method);
        } else {
            $service = $name;
            $callback = $this->app[$name];
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(sprintf('Service "%s" is not callable.', $service));
        }

        return $callback;
    }

    /**
     * Returns a callable given its string representation if it is a valid service method.
     *
     * @param string $name
     *
     * @return string|callable A callable value or the string passed in
     *
     * @throws \InvalidArgumentException In case the method does not exist.
     */
    public function resolveCallback($name)
    {
        return $this->isValid($name) ? $this->convertCallback($name) : $name;
    }
}
