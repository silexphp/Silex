<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Route;

use Silex\Route as BaseRoute;

/**
 * Allows Empty Paths in routes. Useful for mounting.
 *
 * @author RJ Garcia <rj@bighead.net>
 */
class EmptyPathRoute extends BaseRoute
{
    private $pathIsEmpty = false;

    public function getPath()
    {
        if (!$this->pathIsEmpty) {
            return parent::getPath();
        }

        return '';
    }

    public function setPath($path)
    {
        $this->pathIsEmpty = $path === '';

        return parent::setPath($path);
    }

    public function serialize()
    {
        $data = unserialize(parent::serialize());
        $data['path_is_empty'] = $this->pathIsEmpty;

        return serialize($data);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->pathIsEmpty = $data['path_is_empty'];

        parent::unserialize($serialized);
    }
}
