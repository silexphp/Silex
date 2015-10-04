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
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

/**
 * Symfony Asset component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AssetServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['assets.packages'] = function ($app) {
            $packages = array();
            foreach ($app['assets.named_packages'] as $name => $package) {
                $version = $app['assets.strategy_factory'](isset($package['version']) ? $package['version'] : '', isset($package['version_format']) ? $package['version_format'] : null);

                $packages[$name] = $app['assets.package_factory'](isset($package['base_path']) ? $package['base_path'] : '', isset($package['base_urls']) ? $package['base_urls'] : array(), $version, $name);
            }

            return new Packages($app['assets.default_package'], $packages);
        };

        $app['assets.default_package'] = function ($app) {
            $version = $app['assets.strategy_factory']($app['assets.version'], $app['assets.version_format']);

            return $app['assets.package_factory']($app['assets.base_path'], $app['assets.base_urls'], $version, 'default');
        };

        $app['assets.context'] = function ($app) {
            return new RequestStackContext($app['request_stack']);
        };

        $app['assets.base_path'] = '';
        $app['assets.base_urls'] = array();
        $app['assets.version'] = null;
        $app['assets.version_format'] = null;

        $app['assets.named_packages'] = array();

        // prototypes

        $app['assets.strategy_factory'] = $app->protect(function ($version, $format) use ($app) {
            if (!$version) {
                return new EmptyVersionStrategy();
            }

            return new StaticVersionStrategy($version, $format);
        });

        $app['assets.package_factory'] = $app->protect(function ($basePath, $baseUrls, $version, $name) use ($app) {
            if ($basePath && $baseUrls) {
                throw new \LogicException(sprintf('Asset package "%s" cannot have base URLs and base paths.', $name));
            }

            if (!$baseUrls) {
                return new PathPackage($basePath, $version, $app['assets.context']);
            }

            return new UrlPackage($baseUrls, $version, $app['assets.context']);
        });
    }
}
