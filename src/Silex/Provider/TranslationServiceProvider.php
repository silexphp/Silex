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
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Formatter\MessageFormatter;
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
        };

        if (isset($app['request_stack'])) {
            $app['translator.listener'] = function ($app) {
                return new TranslatorListener($app['translator'], $app['request_stack']);
            };
        }

        $app['translator.message_selector'] = function () {
            if (Kernel::VERSION_ID < 30400) {
                return new MessageSelector();
            }

            return new MessageFormatter();
        };

        $app['translator.resources'] = function ($app) {
            return [];
        };

        $app['translator.domains'] = [];
        $app['locale_fallbacks'] = ['en'];
        $app['translator.cache_dir'] = null;
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        if (isset($app['translator.listener'])) {
            $dispatcher->addSubscriber($app['translator.listener']);
        }
    }
}
