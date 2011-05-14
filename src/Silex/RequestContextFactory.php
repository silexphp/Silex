<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

/**
 * Creates a RequestContext from a Request.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class RequestContextFactory
{
    /**
     * Creates the RequestContext instance.
     *
     * @param Request $request The input Request
     * @param int $defaultHttpPort The default port for HTTP
     * @param int $defaultHttpsPort The default port for HTTPS
     *
     * @return RequestContext the RequestContext
     */
    public function create(Request $request, $defaultHttpPort = 80, $defaultHttpsPort = 443)
    {
        return new RequestContext(
            $request->getBaseUrl(),
            $request->getMethod(),
            $request->getHost(),
            $request->getScheme(),
            !$request->isSecure() ? $request->getPort() : $defaultHttpPort,
            $request->isSecure() ? $request->getPort() : $defaultHttpsPort
        );
    }
}
