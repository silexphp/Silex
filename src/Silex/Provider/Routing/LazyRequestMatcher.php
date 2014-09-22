<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Implements a lazy UrlMatcher.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
class LazyRequestMatcher implements RequestMatcherInterface
{
    private $factory;

    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Returns the corresponding RequestMatcherInterface instance.
     *
     * @return UrlMatcherInterface
     */
    public function getRequestMatcher()
    {
        $matcher = call_user_func($this->factory);
        if (!$matcher instanceof RequestMatcherInterface) {
            throw new \LogicException("Factory supplied to LazyRequestMatcher must return implementation of Symfony\Component\Routing\RequestMatcherInterface.");
        }

        return $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        return $this->getRequestMatcher()->matchRequest($request);
    }
}
