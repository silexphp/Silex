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

use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;

/**
 * Twig integration for Silex.
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

            if ($app['debug']) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            if (class_exists('Symfony\Bridge\Twig\Extension\RoutingExtension')) {
                if (isset($app['url_generator'])) {
                    $twig->addExtension(new RoutingExtension($app['url_generator']));
                }

                if (isset($app['translator'])) {
                    $twig->addExtension(new TranslationExtension($app['translator']));
                }

                if (isset($app['security.context'])) {
                    $twig->addExtension(new SecurityExtension($app['security.context']));
                }

                if (isset($app['form.factory'])) {
                    if (!isset($app['twig.form.templates'])) {
                        $app['twig.form.templates'] = array('form_div_layout.html.twig');
                    }

                    $twig->addExtension(new FormExtension($app['form.csrf_provider'], $app['twig.form.templates']));

                    // add loader for Symfony built-in form templates
                    $reflected = new \ReflectionClass('Symfony\Bridge\Twig\Extension\FormExtension');
                    $path = dirname($reflected->getFileName()).'/../Resources/views/Form';
                    $app['twig.loader']->addLoader(new \Twig_Loader_Filesystem($path));
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
    }

    public function boot(Application $app)
    {
        if (isset($app['twig.class_path'])) {
            throw new \RuntimeException('You have provided the twig.class_path parameter. The autoloader has been removed from Silex. It is recommended that you use Composer to manage your dependencies and handle your autoloading. If you are already using Composer, you can remove the parameter. See http://getcomposer.org for more information.');
        }
    }
}
