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
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Form\TwigRenderer;

/**
 * Twig integration for Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    protected $providerName;

    public function __construct($name = "twig") {
        $this->providerName = $name;
    }

    public function register(Application $app)
    {
        $providerName = $this->providerName;
        $app[$providerName.'.options'] = array();
        $app[$providerName.'.form.templates'] = array('form_div_layout.html.twig');
        $app[$providerName.'.path'] = array();
        $app[$providerName.'.templates'] = array();

        $app[$providerName] = $app->share(function ($app ) use ( $providerName ) {
            $app[$providerName.'.options'] = array_replace(
                array(
                     'charset'          => $app['charset'],
                     'debug'            => $app['debug'],
                     'strict_variables' => $app['debug'],
                ), $app[$providerName.'.options']
            );

            $twig = new \Twig_Environment($app[$providerName.'.loader'], $app['twig.options']);
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

                if (isset($app['security'])) {
                    $twig->addExtension(new SecurityExtension($app['security']));
                }

                if (isset($app['form.factory'])) {
                    $app['twig.form.engine'] = $app->share(function ($app) {
                        return new TwigRendererEngine($app[$providerName.'.form.templates']);
                    });

                    $app['twig.form.renderer'] = $app->share(function ($app) {
                        return new TwigRenderer($app[$providerName.'.form.engine'], $app['form.csrf_provider']);
                    });

                    $twig->addExtension(new FormExtension($app[$providerName.'.form.renderer']));

                    // add loader for Symfony built-in form templates
                    $reflected = new \ReflectionClass('Symfony\Bridge\Twig\Extension\FormExtension');
                    $path = dirname($reflected->getFileName()).'/../Resources/views/Form';
                    $app[$providerName.'.loader']->addLoader(new \Twig_Loader_Filesystem($path));
                }
            }

            return $twig;
        });

        $app[$providerName.'.loader.filesystem'] = $app->share(function ($app) use ( $providerName ) {
            return new \Twig_Loader_Filesystem($app[$providerName.'.path']);
        });

        $app[$providerName.'.loader.array'] = $app->share(function ($app) use ( $providerName ) {
            return new \Twig_Loader_Array($app[$providerName.'.templates']);
        });

        $app[$providerName.'.loader'] = $app->share(function ($app) use ( $providerName ) {
            return new \Twig_Loader_Chain(array(
                                               $app[$providerName.'.loader.array'],
                                               $app[$providerName.'.loader.filesystem'],
                                          ));
        });
    }

    public function boot(Application $app)
    {
    }
}
