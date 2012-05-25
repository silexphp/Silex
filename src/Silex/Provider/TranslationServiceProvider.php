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

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;

/**
 * Symfony Translation component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['translator'] = $app->share(function () use ($app) {
            $translator = new Translator(isset($app['locale']) ? $app['locale'] : 'en', $app['translator.message_selector']);

            if (isset($app['locale_fallback'])) {
                $translator->setFallbackLocale($app['locale_fallback']);
            }

            $translator->addLoader('array', $app['translator.loader']);

            if (isset($app['translator.messages'])) {
                foreach ($app['translator.messages'] as $locale => $messages) {
                    $translator->addResource('array', $messages, $locale);
                }
            }

            if (isset($app['translator.domains'])) {
                foreach ($app['translator.domains'] as $domain => $data) {
                    foreach ($data as $locale => $messages) {
                        $translator->addResource('array', $messages, $locale, $domain);
                    }
                }
            }

            return $translator;
        });

        $app['translator.loader'] = $app->share(function () {
            return new ArrayLoader();
        });

        $app['translator.message_selector'] = $app->share(function () {
            return new MessageSelector();
        });
    }
}
