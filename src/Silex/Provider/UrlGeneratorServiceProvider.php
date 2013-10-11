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
use Silex\Application;

use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Symfony Routing component Provider for URL generation.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class UrlGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['url_generator'] = $app->share(function ($app) {
            $app->flush();

            return new UrlGenerator($app['routes'], $app['request_context']);
        });
    }
}
