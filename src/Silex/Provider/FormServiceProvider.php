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
use Symfony\Component\Form\Forms;
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

        $app['form.types'] = function ($app) {
            return array();
        };

        $app['form.type.extensions'] = function ($app) {
            return array();
        };

        $app['form.type.guessers'] = function ($app) {
            return array();
        };

        $app['form.extension.csrf'] = function ($app) {
            if (isset($app['translator'])) {
                return new CsrfExtension($app['csrf.token_manager'], $app['translator']);
            }

            return new CsrfExtension($app['csrf.token_manager']);
        };

        $app['form.extension.silex'] = function ($app) {
            return new Form\SilexFormExtension($app, $app['form.types'], $app['form.type.extensions'], $app['form.type.guessers']);
        };

        $app['form.extensions'] = function ($app) {
            $extensions = array(
                $app['form.extension.silex'],
                new HttpFoundationExtension(),
            );

            if (isset($app['csrf.token_manager'])) {
                $extensions[] = $app['form.extension.csrf'];
            }

            if (isset($app['validator'])) {
                $extensions[] = new FormValidatorExtension($app['validator']);
            }

            return $extensions;
        };

        $app['form.factory'] = function ($app) {
            return Forms::createFormFactoryBuilder()
                ->addExtensions($app['form.extensions'])
                ->setResolvedTypeFactory($app['form.resolved_type_factory'])
                ->getFormFactory()
            ;
        };

        $app['form.resolved_type_factory'] = function ($app) {
            return new ResolvedFormTypeFactory();
        };
    }
}
