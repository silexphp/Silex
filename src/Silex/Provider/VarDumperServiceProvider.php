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
use Silex\Api\BootableProviderInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Symfony Var Dumper component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class VarDumperServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['var_dumper.cli_dumper'] = function ($app) {
            return new CliDumper($app['var_dumper.dump_destination'], $app['charset']);
        };

        $app['var_dumper.html_dumper'] = function ($app) {
            return new HtmlDumper($app['var_dumper.dump_destination'], $app['charset']);
        };

        $app['var_dumper.cloner'] = function ($app) {
            return new VarCloner();
        };

        $app['var_dumper.dump_destination'] = null;
    }

    public function boot(Application $app)
    {
        if (!$app['debug']) {
            return;
        }

        // This code is here to lazy load the dump stack.
        VarDumper::setHandler(function ($var) use ($app) {
            $handler = function ($var) use ($app) {
                'cli' === PHP_SAPI
                    ? $app['var_dumper.cli_dumper']->dump($app['var_dumper.cloner']->cloneVar($var))
                    : $app['var_dumper.html_dumper']->dump($app['var_dumper.cloner']->cloneVar($var))
                ;
            };
            $handler($var);
        });
    }
}
