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

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * UrlGenerator trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait UrlGeneratorTrait
{
    /**
     * Generates a path from the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return string The generated path
     */
    public function path($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Generates an absolute URL from the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return string The generated URL
     */
    public function url($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Overrides the default redirect behaviour of the Application class
     *
     * @param string  $url        The URL to redirect to
     * @param array   $parameters An array of paramaters
     * @param integer $status     The status code (302 by default)
     *
     * @return RedirectResponse
     */
    public function redirectRoute($route, $parameters = array(), $status = 302)
    {
        return $this->redirect($this->path($route, $parameters), $status);
    }
}
