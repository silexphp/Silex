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
use Silex\Provider\Translation\Translator;
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
    public function register(Container $app)
    {
        $app['translator'] = function ($app) {
            if (!isset($app['locale'])) {
                throw new \LogicException('You must register the LocaleServiceProvider to use the TranslationServiceProvider');
            }

            $translator = new Translator($app, $app['translator.message_selector']);
            $translator->setFallbackLocales($app['locale_fallbacks']);
            $translator->addLoader('array', new ArrayLoader());
            $translator->addLoader('xliff', new XliffFileLoader());

            foreach ($app['translator.domains'] as $domain => $data) {
                foreach ($data as $locale => $messages) {
                    $translator->addResource('array', $messages, $locale, $domain);
                }
            }

            return $translator;
        };

        $app['translator.message_selector'] = function () {
            return new MessageSelector();
        };

        $app['translator.domains'] = array();
        $app['locale_fallbacks'] = array('en');
    }
}
