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
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer;
use Symfony\Component\HttpKernel\Fragment\HIncludeFragmentRenderer;
use Symfony\Component\HttpKernel\EventListener\FragmentListener;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * HttpKernel Fragment integration for Silex.
 *
 * This service provider requires Symfony 2.4+.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HttpFragmentServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!class_exists('Symfony\Component\HttpFoundation\RequestStack')) {
            throw new \LogicException('The HTTP Fragment service provider only works with Symfony 2.4+.');
        }

        $app['fragment.handler'] = $app->share(function ($app) {
            return new FragmentHandler($app['fragment.renderers'], $app['debug'], $app['request_stack']);
        });

        $app['fragment.renderer.inline'] = $app->share(function ($app) {
            $renderer = new InlineFragmentRenderer($app['kernel'], $app['dispatcher']);
            $renderer->setFragmentPath($app['fragment.path']);

            return $renderer;
        });

        $app['fragment.renderer.hinclude'] = $app->share(function ($app) {
            $renderer = new HIncludeFragmentRenderer(null, $app['uri_signer'], $app['fragment.renderer.hinclude.global_template'], $app['charset']);
            $renderer->setFragmentPath($app['fragment.path']);

            return $renderer;
        });

        $app['fragment.renderer.esi'] = $app->share(function ($app) {
            $renderer = new EsiFragmentRenderer($app['http_cache.esi'], $app['fragment.renderer.inline']);
            $renderer->setFragmentPath($app['fragment.path']);

            return $renderer;
        });

        $app['fragment.listener'] = $app->share(function ($app) {
            return new FragmentListener($app['uri_signer'], $app['fragment.path']);
        });

        $app['uri_signer'] = $app->share(function ($app) {
            return new UriSigner($app['uri_signer.secret']);
        });

        $app['uri_signer.secret'] = md5(__DIR__);
        $app['fragment.path'] = '/_fragment';
        $app['fragment.renderer.hinclude.global_template'] = null;
        $app['fragment.renderers'] = $app->share(function ($app) {
            $renderers = array($app['fragment.renderer.inline'], $app['fragment.renderer.hinclude']);

            if (isset($app['http_cache.esi'])) {
                $renderers[] = $app['fragment.renderer.esi'];
            }

            return $renderers;
        });
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber($app['fragment.listener']);
    }
}
