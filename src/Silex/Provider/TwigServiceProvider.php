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
use Silex\Provider\Twig\RuntimeLoader;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\DumpExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * Twig integration for Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['twig.options'] = [];
        $app['twig.form.templates'] = ['form_div_layout.html.twig'];
        $app['twig.path'] = [];
        $app['twig.templates'] = [];

        $app['twig.date.format'] = 'F j, Y H:i';
        $app['twig.date.interval_format'] = '%d days';
        $app['twig.date.timezone'] = null;

        $app['twig.number_format.decimals'] = 0;
        $app['twig.number_format.decimal_point'] = '.';
        $app['twig.number_format.thousands_separator'] = ',';

        $app['twig'] = function ($app) {
            $app['twig.options'] = array_replace(
                [
                    'charset' => $app['charset'],
                    'debug' => $app['debug'],
                    'strict_variables' => $app['debug'],
                ], $app['twig.options']
            );

            $twig = $app['twig.environment_factory']($app);
            // registered for BC, but should not be used anymore
            // deprecated and should probably be removed in Silex 3.0
            $twig->addGlobal('app', $app);

            $coreExtension = $twig->getExtension('Twig_Extension_Core');

            $coreExtension->setDateFormat($app['twig.date.format'], $app['twig.date.interval_format']);

            if (null !== $app['twig.date.timezone']) {
                $coreExtension->setTimezone($app['twig.date.timezone']);
            }

            $coreExtension->setNumberFormat($app['twig.number_format.decimals'], $app['twig.number_format.decimal_point'], $app['twig.number_format.thousands_separator']);

            if ($app['debug']) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            if (class_exists('Symfony\Bridge\Twig\Extension\RoutingExtension')) {
                $app['twig.app_variable'] = function ($app) {
                    $var = new AppVariable();
                    if (isset($app['security.token_storage'])) {
                        $var->setTokenStorage($app['security.token_storage']);
                    }
                    if (isset($app['request_stack'])) {
                        $var->setRequestStack($app['request_stack']);
                    }
                    $var->setDebug($app['debug']);

                    return $var;
                };

                $twig->addGlobal('global', $app['twig.app_variable']);

                if (isset($app['request_stack'])) {
                    $twig->addExtension(new HttpFoundationExtension($app['request_stack']));
                    $twig->addExtension(new RoutingExtension($app['url_generator']));
                }

                if (isset($app['translator'])) {
                    $twig->addExtension(new TranslationExtension($app['translator']));
                }

                if (isset($app['security.authorization_checker'])) {
                    $twig->addExtension(new SecurityExtension($app['security.authorization_checker']));
                }

                if (isset($app['fragment.handler'])) {
                    $app['fragment.renderer.hinclude']->setTemplating($twig);

                    $twig->addExtension(new HttpKernelExtension($app['fragment.handler']));
                }

                if (isset($app['assets.packages'])) {
                    $twig->addExtension(new AssetExtension($app['assets.packages']));
                }

                if (isset($app['form.factory'])) {
                    $app['twig.form.engine'] = function ($app) use ($twig) {
                        return new TwigRendererEngine($app['twig.form.templates'], $twig);
                    };

                    $app['twig.form.renderer'] = function ($app) {
                        $csrfTokenManager = isset($app['csrf.token_manager']) ? $app['csrf.token_manager'] : null;

                        if (Kernel::VERSION_ID < 30400) {
                            return new TwigRenderer($app['twig.form.engine'], $csrfTokenManager);
                        }

                        return new FormRenderer($app['twig.form.engine'], $csrfTokenManager);
                    };

                    $twig->addExtension(new FormExtension(class_exists(HttpKernelRuntime::class) ? null : $app['twig.form.renderer']));

                    // add loader for Symfony built-in form templates
                    $reflected = new \ReflectionClass('Symfony\Bridge\Twig\Extension\FormExtension');
                    $path = dirname($reflected->getFileName()).'/../Resources/views/Form';
                    $app['twig.loader']->addLoader(new \Twig_Loader_Filesystem($path));
                }

                if (isset($app['var_dumper.cloner'])) {
                    $twig->addExtension(new DumpExtension($app['var_dumper.cloner']));
                }

                if (class_exists(HttpKernelRuntime::class)) {
                    $twig->addRuntimeLoader($app['twig.runtime_loader']);
                }

                if (class_exists(HttpHeaderSerializer::class) && class_exists(WebLinkExtension::class)) {
                    $twig->addExtension(new WebLinkExtension($app['request_stack']));
                }
            }

            return $twig;
        };

        $app['twig.loader.filesystem'] = function ($app) {
            $loader = new \Twig_Loader_Filesystem();
            foreach (is_array($app['twig.path']) ? $app['twig.path'] : [$app['twig.path']] as $key => $val) {
                if (is_string($key)) {
                    $loader->addPath($key, $val);
                } else {
                    $loader->addPath($val);
                }
            }

            return $loader;
        };

        $app['twig.loader.array'] = function ($app) {
            return new \Twig_Loader_Array($app['twig.templates']);
        };

        $app['twig.loader'] = function ($app) {
            return new \Twig_Loader_Chain([
                $app['twig.loader.array'],
                $app['twig.loader.filesystem'],
            ]);
        };

        $app['twig.environment_factory'] = $app->protect(function ($app) {
            return new \Twig_Environment($app['twig.loader'], $app['twig.options']);
        });

        $app['twig.runtime.httpkernel'] = function ($app) {
            return new HttpKernelRuntime($app['fragment.handler']);
        };

        $app['twig.runtimes'] = function ($app) {
            $runtimes = [
                HttpKernelRuntime::class => 'twig.runtime.httpkernel',
            ];

            if (Kernel::VERSION_ID < 30400) {
                $runtimes[TwigRenderer::class] = 'twig.form.renderer';
            } else {
                $runtimes[FormRenderer::class] = 'twig.form.renderer';
            }

            return $runtimes;
        };

        $app['twig.runtime_loader'] = function ($app) {
            return new RuntimeLoader($app, $app['twig.runtimes']);
        };
    }
}
