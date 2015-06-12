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
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\EventListener\TranslatorListener;
use Silex\Api\EventListenerProviderInterface;

/**
 * Symfony Translation component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TranslationServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['translator'] = function ($app) {
            if (!isset($app['locale'])) {
                throw new \LogicException('You must define \'locale\' parameter or register the LocaleServiceProvider to use the TranslationServiceProvider');
            }

            $translator = new Translator($app['locale'], $app['translator.message_selector'], $app['translator.cache_dir'], $app['debug']);
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

        $app['translator.listener'] = function ($app) {
            return new TranslatorListener($app['translator'], $app['request_stack']);
        };

        $app['translator.message_selector'] = function () {
            return new MessageSelector();
        };

        $app['translator.domains'] = array();
        $app['locale_fallbacks'] = array('en');
        $app['translator.cache_dir'] = null;
    }


    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['translator.listener']);
    }
}
