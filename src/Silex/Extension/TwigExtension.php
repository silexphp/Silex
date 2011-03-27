<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Extension;

use Silex\Application;
use Silex\ExtensionInterface;

class TwigExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app['twig'] = $app->share(function () use ($app) {
            $twig = new \Twig_Environment($app['twig.loader'], isset($app['twig.options']) ? $app['twig.options'] : array());
            $twig->addGlobal('app', $app);
            if (isset($app['twig.configure'])) {
                $app['twig.configure']($twig);
            }

            return $twig;
        });

        $app['twig.loader'] = $app->share(function () use ($app) {
            if (isset($app['twig.templates'])) {
                return new \Twig_Loader_Array($app['twig.templates']);
            } else {
                return new \Twig_Loader_Filesystem($app['twig.path']);
            }
        });

        if (isset($app['twig.class_path'])) {
            $app['autoloader']->registerPrefix('Twig_', $app['twig.class_path']);
        }
    }
}
