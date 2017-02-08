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
use Silex\Provider\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Validation;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

/**
 * Symfony Validator component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ValidatorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['validator'] = function ($app) {
            return $app['validator.builder']->getValidator();
        };

        $app['validator.builder'] = function ($app) {
            $builder = Validation::createValidatorBuilder();
            $builder->setConstraintValidatorFactory($app['validator.validator_factory']);
            $builder->setTranslationDomain('validators');
            $builder->addObjectInitializers($app['validator.object_initializers']);
            $builder->setMetadataFactory($app['validator.mapping.class_metadata_factory']);
            if (isset($app['translator'])) {
                $builder->setTranslator($app['translator']);
            }

            return $builder;
        };

        $app['validator.mapping.class_metadata_factory'] = function ($app) {
            $loader = null;

            if ($app->offsetExists('validator.mapping.use_annotation') && $app['validator.mapping.use_annotation'] === true) {
                $loader = new AnnotationLoader(new AnnotationReader());
            } else {
                $loader = new StaticMethodLoader();
            }

            $cache = null;

            if ($app->offsetExists('validator.mapping.cache')) {
                $cache = $app['validator.mapping.cache'];
            }

            return new LazyLoadingMetadataFactory($loader, $cache);
        };

        $app['validator.validator_factory'] = function () use ($app) {
            return new ConstraintValidatorFactory($app, $app['validator.validator_service_ids']);
        };

        $app['validator.object_initializers'] = function ($app) {
            return array();
        };

        $app['validator.validator_service_ids'] = array();
    }
}
