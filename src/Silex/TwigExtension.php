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

class TwigExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app['twig'] = $app->asShared(function () use ($app) {
            $twig = new \Twig_Environment($app['twig.loader'], $app['twig.options']);
            $twig->addGlobal('app', $app);

            return $twig;
        });

        $app['twig.loader'] = $app->asShared(function () use ($app) {
            return new \Twig_Loader_Filesystem($app['twig.path']);
        });
    }
}
