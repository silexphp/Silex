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
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension as FormValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Symfony Form component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!class_exists('Locale') && !class_exists('Symfony\Component\Locale\Stub\StubLocale')) {
            throw new \RuntimeException('You must either install the PHP intl extension or the Symfony Locale Component to use the Form extension.');
        }

        if (!class_exists('Locale')) {
            $r = new \ReflectionClass('Symfony\Component\Locale\Stub\StubLocale');
            $path = dirname(dirname($r->getFilename())).'/Resources/stubs';

            require_once $path.'/functions.php';
            require_once $path.'/Collator.php';
            require_once $path.'/IntlDateFormatter.php';
            require_once $path.'/Locale.php';
            require_once $path.'/NumberFormatter.php';
        }

        $app['form.secret'] = md5(__DIR__);

        $app['form.types'] = $app->share(function ($app) {
            return array();
        });

        $app['form.type.extensions'] = $app->share(function ($app) {
            return array();
        });

        $app['form.type.guessers'] = $app->share(function ($app) {
            return array();
        });

        $app['form.extension.csrf'] = $app->share(function ($app) {
            if (isset($app['translator'])) {
                return new CsrfExtension($app['form.csrf_provider'], $app['translator']);
            }

            return new CsrfExtension($app['form.csrf_provider']);
        });

        $app['form.extensions'] = $app->share(function ($app) {
            $extensions = array(
                $app['form.extension.csrf'],
                new HttpFoundationExtension(),
            );

            if (isset($app['validator'])) {
                $extensions[] = new FormValidatorExtension($app['validator']);
            }

            return $extensions;
        });

        $app['form.factory'] = $app->share(function ($app) {
            return Forms::createFormFactoryBuilder()
                ->addExtensions($app['form.extensions'])
                ->addTypes($app['form.types'])
                ->addTypeExtensions($app['form.type.extensions'])
                ->addTypeGuessers($app['form.type.guessers'])
                ->setResolvedTypeFactory($app['form.resolved_type_factory'])
                ->getFormFactory()
            ;
        });

        $app['form.resolved_type_factory'] = $app->share(function ($app) {
            return new ResolvedFormTypeFactory();
        });

        $app['form.csrf_provider'] = $app->share(function ($app) {
            if (!class_exists('Symfony\Component\Form\Extension\DataCollector\DataCollectorExtension')) {
                // Symfony 2.3
                if (isset($app['session'])) {
                    return new SessionCsrfProvider($app['session'], $app['form.secret']);
                }

                return new DefaultCsrfProvider($app['form.secret']);
            } else {
                // Symfony 2.4+
                $storage = isset($app['session']) ? new SessionTokenStorage($app['session']) : new NativeSessionTokenStorage();

                return new CsrfTokenManager(null, $storage);
            }
        });
    }

    public function boot(Application $app)
    {
    }
}
