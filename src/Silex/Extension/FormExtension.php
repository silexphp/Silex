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
use Symfony\Component\HttpFoundation\File\TemporaryStorage;
use Symfony\Component\Form\CsrfProvider\DefaultCsrfProvider;
use Symfony\Component\Form\Type\Loader\DefaultTypeLoader;
use Symfony\Component\Form\FormFactory;

class FormExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app['form.csrf.secret'] = '12345';
        $app['form.storage.secret'] = 'abcdef';

        $app['form.factory'] = $app->share(function () use ($app) {
            return new FormFactory($app['form.type_loader']);
        });

        $app['form.type_loader'] = $app->share(function () use ($app) {
            if (!isset($app['validator'])) {
                throw new \RuntimeException(sprintf('The FormExtension needs the ValidationExtension to be registered.'));
            }

            return new DefaultTypeLoader($app['validator'], $app['form.csrf.provider'], $app['form.storage']);
        });

        $app['form.csrf.provider'] = $app->share(function () use ($app) {
            return new DefaultCsrfProvider($app['form.csrf.secret']);
        });

        $app['form.storage'] = $app->share(function () use ($app) {
            return new TemporaryStorage($app['form.storage.secret']);
        });

        if (isset($app['form.class_path'])) {
            $app['autoloader']->registerNamespace('Symfony\\Component\\Form', $app['form.class_path']);
        }
    }
}
