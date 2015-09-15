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
use Silex\ConstraintValidatorFactory;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Validation;

/**
 * Symfony Validator component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ValidatorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['validator'] = $app->share(function ($app) {
            return $app['validator.builder']->getValidator();
        });

        $app['validator.builder'] = $app->share(function ($app) {
            $builder = Validation::createValidatorBuilder();
            $builder->setConstraintValidatorFactory($app['validator.validator_factory']);
            $builder->setTranslationDomain('validators');
            $builder->addObjectInitializers($app['validator.object_initializers']);
            $builder->setMetadataFactory($app['validator.mapping.class_metadata_factory']);
            if (isset($app['translator'])) {
                $builder->setTranslator($app['translator']);
            }

            return $builder;
        });

        $app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) {
            if (class_exists('Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory')) {
                return new LazyLoadingMetadataFactory(new StaticMethodLoader());
            }

            return new ClassMetadataFactory(new StaticMethodLoader());
        });

        $app['validator.validator_factory'] = $app->share(function () use ($app) {
            return new ConstraintValidatorFactory($app, $app['validator.validator_service_ids']);
        });

        $app['validator.object_initializers'] = $app->share(function ($app) {
            return array();
        });

        $app['validator.validator_service_ids'] = array();
    }

    public function boot(Application $app)
    {
    }
}
