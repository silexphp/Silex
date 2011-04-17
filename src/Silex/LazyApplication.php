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

use Symfony\Component\HttpFoundation\Request;

/**
 * A Lazy application wrapper.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LazyApplication
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function __invoke(Request $request, $prefix)
    {
        if (!$this->app instanceof Application) {
            $this->app = require $this->app;
        }

        return $this->app->__invoke($request, $prefix);
    }
}
