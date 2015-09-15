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
use Silex\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;

/**
 * Symfony Translation component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['translator'] = $app->share(function ($app) {
            $translator = new Translator($app, $app['translator.message_selector'], $app['translator.cache_dir'], $app['debug']);

            // Handle deprecated 'locale_fallback'
            if (isset($app['locale_fallback'])) {
                $app['locale_fallbacks'] = (array) $app['locale_fallback'];
            }

            $translator->setFallbackLocales($app['locale_fallbacks']);

            $translator->addLoader('array', new ArrayLoader());
            $translator->addLoader('xliff', new XliffFileLoader());

            if (isset($app['validator'])) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validation');
                $file = dirname($r->getFilename()).'/Resources/translations/validators.'.$app['locale'].'.xlf';
                if (file_exists($file)) {
                    $translator->addResource('xliff', $file, $app['locale'], 'validators');
                }
            }

            if (isset($app['form.factory'])) {
                $r = new \ReflectionClass('Symfony\Component\Form\Form');
                $file = dirname($r->getFilename()).'/Resources/translations/validators.'.$app['locale'].'.xlf';
                if (file_exists($file)) {
                    $translator->addResource('xliff', $file, $app['locale'], 'validators');
                }
            }

            // Register default resources
            foreach ($app['translator.resources'] as $resource) {
                $translator->addResource($resource[0], $resource[1], $resource[2], $resource[3]);
            }

            foreach ($app['translator.domains'] as $domain => $data) {
                foreach ($data as $locale => $messages) {
                    $translator->addResource('array', $messages, $locale, $domain);
                }
            }

            return $translator;
        });

        $app['translator.resources'] = function ($app) {
            return array();
        };

        $app['translator.message_selector'] = $app->share(function () {
            return new MessageSelector();
        });

        $app['translator.domains'] = array();
        $app['locale_fallbacks'] = array('en');
        $app['translator.cache_dir'] = null;
    }

    public function boot(Application $app)
    {
    }
}
