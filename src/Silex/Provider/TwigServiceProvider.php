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

use Symfony\Bridge\Twig\Extension\RoutingExtension as TwigRoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension as TwigTranslationExtension;
use Symfony\Bridge\Twig\Extension\FormExtension as TwigFormExtension;

/**
 * Twig Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['twig'] = $app->share(function () use ($app) {
            $app['twig.options'] = array_replace(
                array(
                    'charset'          => $app['charset'],
                    'debug'            => $app['debug'],
                    'strict_variables' => $app['debug'],
                ),
                isset($app['twig.options']) ? $app['twig.options'] : array()
            );

            $twig = new \Twig_Environment($app['twig.loader'], $app['twig.options']);
            $twig->addGlobal('app', $app);
            $twig->addExtension(new TwigCoreExtension());

            if (isset($app['symfony_bridges'])) {
                if (isset($app['url_generator'])) {
                    $twig->addExtension(new TwigRoutingExtension($app['url_generator']));
                }

                if (isset($app['translator'])) {
                    $twig->addExtension(new TwigTranslationExtension($app['translator']));
                }

                if (isset($app['form.factory'])) {
                    $twig->addExtension(new TwigFormExtension(array('form_div_layout.html.twig')));
                }
            }

            if (isset($app['twig.configure'])) {
                $app['twig.configure']($twig);
            }

            return $twig;
        });

        $app['twig.loader.filesystem'] = $app->share(function () use ($app) {
            return new \Twig_Loader_Filesystem(isset($app['twig.path']) ? $app['twig.path'] : array());
        });

        $app['twig.loader.array'] = $app->share(function () use ($app) {
            return new \Twig_Loader_Array(isset($app['twig.templates']) ? $app['twig.templates'] : array());
        });

        $app['twig.loader'] = $app->share(function () use ($app) {
            return new \Twig_Loader_Chain(array(
                $app['twig.loader.filesystem'],
                $app['twig.loader.array'],
            ));
        });

        if (isset($app['twig.class_path'])) {
            $app['autoloader']->registerPrefix('Twig_', $app['twig.class_path']);
        }
    }
}
