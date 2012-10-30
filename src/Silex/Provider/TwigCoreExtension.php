<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;

/**
 * Twig extension.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TwigCoreExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'render'        => new \Twig_Function_Method($this, 'render', array('needs_environment' => true, 'is_safe' => array('html'))),
            'render_route'  => new \Twig_Function_Method($this, 'renderRoute', array('needs_environment' => true, 'is_safe' => array('html'))),
        );
    }

    public function render(\Twig_Environment $twig, $uri)
    {
        $globals = $twig->getGlobals();
        $app = $globals['app'];
        $request = $app['request'];

        $uri = $request->getBaseUrl().$uri;
        $subRequest = Request::create($uri, 'get', array(), $request->cookies->all(), array(), $request->server->all());

        return $this->handleSubRequest($app, $request, $subRequest);
    }

    public function renderRoute(\Twig_Environment $twig, $routeName, $parameters)
    {
        if (!class_exists('Symfony\Component\Routing\Generator\UrlGenerator')) {
            throw new \RuntimeException('You cannot use render_route without the Symfony2 Routing component.');
        }

        $globals = $twig->getGlobals();
        $app = $globals['app'];
        $request = $app['request'];

        $generator = new UrlGenerator($app['routes'], $app['request_context']);
        $uri = $generator->generate($routeName, $parameters);

        $subRequest = Request::create($uri, 'get', array(), $request->cookies->all(), array(), $request->server->all());

        return $this->handleSubRequest($app, $request, $subRequest);
    }

    public function getName()
    {
        return 'silex';
    }

    private function handleSubRequest(Application $app, Request $request, Request $subRequest)
    {
        if ($request->getSession()) {
            $subRequest->setSession($request->getSession());
        }

        $response = $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(sprintf('Error when rendering "%s" (Status code is %s).', $request->getUri(), $response->getStatusCode()));
        }

        return $response->getContent();
    }
}
