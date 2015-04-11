<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use Silex\Application;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\LocaleServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * TranslationProvider test cases.
 *
 * @author Daniel Tschinder <daniel@tschinder.de>
 */
class TranslationServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Application
     */
    protected function getPreparedApp()
    {
        $app = new Application();

        $app->register(new LocaleServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app['translator.domains'] = array(
            'messages' => array(
                'en' => array(
                    'key1' => 'The translation',
                    'key_only_english' => 'Foo',
                    'key2' => 'One apple|%count% apples',
                    'test' => array(
                        'key' => 'It works',
                    ),
                ),
                'de' => array(
                    'key1' => 'The german translation',
                    'key2' => 'One german apple|%count% german apples',
                    'test' => array(
                        'key' => 'It works in german',
                    ),
                ),
            ),
        );

        return $app;
    }

    public function transChoiceProvider()
    {
        return array(
            array('key2', 0, null, '0 apples'),
            array('key2', 1, null, 'One apple'),
            array('key2', 2, null, '2 apples'),
            array('key2', 0, 'de', '0 german apples'),
            array('key2', 1, 'de', 'One german apple'),
            array('key2', 2, 'de', '2 german apples'),
            array('key2', 0, 'ru', '0 apples'), // fallback
            array('key2', 1, 'ru', 'One apple'), // fallback
            array('key2', 2, 'ru', '2 apples'), // fallback
        );
    }

    public function transProvider()
    {
        return array(
            array('key1', null, 'The translation'),
            array('key1', 'de', 'The german translation'),
            array('key1', 'ru', 'The translation'), // fallback
            array('test.key', null, 'It works'),
            array('test.key', 'de', 'It works in german'),
            array('test.key', 'ru', 'It works'), // fallback
        );
    }

    /**
     * @dataProvider transProvider
     */
    public function testTransForDefaultLanguage($key, $locale, $expected)
    {
        $app = $this->getPreparedApp();

        $result = $app['translator']->trans($key, array(), null, $locale);

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider transChoiceProvider
     */
    public function testTransChoiceForDefaultLanguage($key, $number, $locale, $expected)
    {
        $app = $this->getPreparedApp();

        $result = $app['translator']->transChoice($key, $number, array('%count%' => $number), null, $locale);
        $this->assertEquals($expected, $result);
    }

    public function testFallbacks()
    {
        $app = $this->getPreparedApp();
        $app['locale_fallbacks'] = array('de', 'en');

        // fallback to english
        $result = $app['translator']->trans('key_only_english', array(), null, 'ru');
        $this->assertEquals('Foo', $result);

        // fallback to german
        $result = $app['translator']->trans('key1', array(), null, 'ru');
        $this->assertEquals('The german translation', $result);
    }

    public function testLocale()
    {
        $app = $this->getPreparedApp();
        $app->get('/', function () use ($app) { return $app['translator']->getLocale(); });
        $response = $app->handle(Request::create('/'));
        $this->assertEquals('en', $response->getContent());

        $app = $this->getPreparedApp();
        $app->get('/', function () use ($app) { return $app['translator']->getLocale(); });
        $request = Request::create('/');
        $request->setLocale('fr');
        $response = $app->handle($request);
        $this->assertEquals('fr', $response->getContent());

        $app = $this->getPreparedApp();
        $app->get('/{_locale}', function () use ($app) { return $app['translator']->getLocale(); });
        $response = $app->handle(Request::create('/es'));
        $this->assertEquals('es', $response->getContent());
    }

    public function testLocaleInSubRequests()
    {
        $app = $this->getPreparedApp();
        $app->get('/embed/{_locale}', function () use ($app) { return $app['translator']->getLocale(); });
        $app->get('/{_locale}', function () use ($app) {
            return $app['translator']->getLocale().
                   $app->handle(Request::create('/embed/es'), HttpKernelInterface::SUB_REQUEST)->getContent().
                   $app['translator']->getLocale();
        });
        $response = $app->handle(Request::create('/fr'));
        $this->assertEquals('fresfr', $response->getContent());

        $app = $this->getPreparedApp();
        $app->get('/embed', function () use ($app) { return $app['translator']->getLocale(); });
        $app->get('/{_locale}', function () use ($app) {
            return $app['translator']->getLocale().
                   $app->handle(Request::create('/embed'), HttpKernelInterface::SUB_REQUEST)->getContent().
                   $app['translator']->getLocale();
        });
        $response = $app->handle(Request::create('/fr'));
        // locale in sub-request must be "en" as this is the value if the sub-request is converted to an ESI
        $this->assertEquals('frenfr', $response->getContent());
    }

    public function testLocaleWithBefore()
    {
        $app = $this->getPreparedApp();
        $app->before(function (Request $request) { $request->setLocale('fr'); }, Application::EARLY_EVENT);
        $app->get('/embed', function () use ($app) { return $app['translator']->getLocale(); });
        $app->get('/', function () use ($app) {
            return $app['translator']->getLocale().
                $app->handle(Request::create('/embed'), HttpKernelInterface::SUB_REQUEST)->getContent().
                $app['translator']->getLocale();
        });
        $response = $app->handle(Request::create('/'));
        // locale in sub-request is "en" as the before filter is only executed for the main request
        $this->assertEquals('frenfr', $response->getContent());
    }
}
