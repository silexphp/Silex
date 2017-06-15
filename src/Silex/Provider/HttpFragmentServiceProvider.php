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

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer;
use Symfony\Component\HttpKernel\Fragment\HIncludeFragmentRenderer;
use Symfony\Component\HttpKernel\EventListener\FragmentListener;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * HttpKernel Fragment integration for Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HttpFragmentServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['fragment.handler'] = function ($app) {
            return new FragmentHandler($app['request_stack'], $app['fragment.renderers'], $app['debug']);
        };

        $app['fragment.renderer.inline'] = function ($app) {
            $renderer = new InlineFragmentRenderer($app['kernel'], $app['dispatcher']);
            $renderer->setFragmentPath($app['fragment.path']);

            return $renderer;
        };

        $app['fragment.renderer.hinclude'] = function ($app) {
            $renderer = new HIncludeFragmentRenderer(null, $app['uri_signer'], $app['fragment.renderer.hinclude.global_template'], $app['charset']);
            $renderer->setFragmentPath($app['fragment.path']);

            return $renderer;
        };

        $app['fragment.renderer.esi'] = function ($app) {
            $renderer = new EsiFragmentRenderer($app['http_cache.esi'], $app['fragment.renderer.inline'], $app['uri_signer']);
            $renderer->setFragmentPath($app['fragment.path']);

            return $renderer;
        };

        $app['fragment.listener'] = function ($app) {
            return new FragmentListener($app['uri_signer'], $app['fragment.path']);
        };

        $app['uri_signer'] = function ($app) {
            return new UriSigner($app['uri_signer.secret']);
        };

        $app['uri_signer.secret'] = md5(__DIR__);
        $app['fragment.path'] = '/_fragment';
        $app['fragment.renderer.hinclude.global_template'] = null;
        $app['fragment.renderers'] = function ($app) {
            $renderers = array($app['fragment.renderer.inline'], $app['fragment.renderer.hinclude']);

            if (isset($app['http_cache.esi'])) {
                $renderers[] = $app['fragment.renderer.esi'];
            }

            return $renderers;
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['fragment.listener']);
    }
}
