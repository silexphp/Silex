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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;
use Symfony\Component\Routing\Matcher\Exception\NotFoundException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RedirectableUrlMatcher extends UrlMatcher implements RedirectableUrlMatcherInterface
{
    private $trailingSlashTest = false;

    /**
     * @see UrlMatcher::match()
     */
    public function match($pathinfo)
    {
        try {
            $parameters = parent::match($pathinfo);
        } catch (NotFoundException $e) {
            if ('/' === substr($pathinfo, -1)) {
                throw $e;
            }

            // try with a / at the end
            $this->trailingSlashTest = true;

            return $this->match($pathinfo.'/');
        }

        if ($this->trailingSlashTest) {
            $this->trailingSlashTest = false;

            return $this->redirect($pathinfo, null);
        }

        return $parameters;
    }

    /**
     * @see RedirectableUrlMatcherInterface::match()
     */
    public function redirect($pathinfo, $route)
    {
        return array(
            '_controller' => function ($url) { return new RedirectResponse($url, 301); },
            'url' => $this->context['base_url'].$pathinfo,
        );
    }
}
