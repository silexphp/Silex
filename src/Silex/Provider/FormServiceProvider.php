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
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension as FormValidatorExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;

/**
 * Symfony Form component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if (!class_exists('Locale')) {
            throw new \RuntimeException('You must either install the PHP intl extension or the Symfony Intl Component to use the Form extension.');
        }

        $app['form.types'] = function ($app) {
            return [];
        };

        $app['form.type.extensions'] = function ($app) {
            return [];
        };

        $app['form.type.guessers'] = function ($app) {
            return [];
        };

        $app['form.extension.csrf'] = function ($app) {
            if (isset($app['translator'])) {
                $translationDomain = isset($app['validator.translation_domain']) ? $app['validator.translation_domain'] : null;

                return new CsrfExtension($app['csrf.token_manager'], $app['translator'], $translationDomain);
            }

            return new CsrfExtension($app['csrf.token_manager']);
        };

        $app['form.extension.silex'] = function ($app) {
            return new Form\SilexFormExtension($app, $app['form.types'], $app['form.type.extensions'], $app['form.type.guessers']);
        };

        $app['form.extensions'] = function ($app) {
            $extensions = [
                new HttpFoundationExtension(),
            ];

            if (isset($app['csrf.token_manager'])) {
                $extensions[] = $app['form.extension.csrf'];
            }

            if (isset($app['validator'])) {
                $extensions[] = new FormValidatorExtension($app['validator']);
            }
            $extensions[] = $app['form.extension.silex'];

            return $extensions;
        };

        $app['form.factory'] = function ($app) {
            return new FormFactory($app['form.registry'], $app['form.resolved_type_factory']);
        };

        $app['form.registry'] = function ($app) {
            return new FormRegistry($app['form.extensions'], $app['form.resolved_type_factory']);
        };

        $app['form.resolved_type_factory'] = function ($app) {
            return new ResolvedFormTypeFactory();
        };
    }
}
