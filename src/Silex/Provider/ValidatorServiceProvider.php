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

use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Mapping\Loader\XmlFileLoader;
use Symfony\Component\Validator\ConstraintValidatorFactory;

/**
 * Symfony Validator component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ValidatorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['validator'] = $app->share(function () use ($app) {
            return new Validator(
                $app['validator.mapping.class_metadata_factory'],
                $app['validator.validator_factory']
            );
        });

        $app['validator.mapping.class_metadata_factory'] = $app->share(function () use ($app) {
            if (isset($app['form.factory'])) {
                $reflClass = new \ReflectionClass('Symfony\\Component\\Form\\FormInterface');

                return new ClassMetadataFactory(new LoaderChain(array(
                    new StaticMethodLoader(),
                    new XmlFileLoader(dirname($reflClass->getFileName()) . '/Resources/config/validation.xml')
                )));
            }

            return new ClassMetadataFactory(new StaticMethodLoader());
        });

        $app['validator.validator_factory'] = $app->share(function () {
            return new ConstraintValidatorFactory();
        });
    }
}
