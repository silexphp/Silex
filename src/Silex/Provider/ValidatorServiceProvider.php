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
use Symfony\Component\Validator\ValidatorBuilder;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;

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
            if (isset($app['translator']) && method_exists($app['translator'], 'addResource')) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validator');

                $app['translator']->addResource('xliff', dirname($r->getFilename()).'/Resources/translations/validators.'.$app['locale'].'.xlf', $app['locale'], 'validators');
            }

            return $app['validator.builder']->getValidator();
        };

        $app['validator.builder'] = $app->share(function () use ($app) {
            $builder = new ValidatorBuilder();
            $builder->setConstraintValidatorFactory($app['validator.validator_factory']);
            $builder->setTranslator(isset($app['translator']) ? $app['translator'] : new DefaultTranslator());
            $builder->setTranslationDomain('validators');
            $builder->setMetadataFactory($app['validator.mapping.class_metadata_factory']);

            return $builder;
        });

        $app['validator.mapping.class_metadata_factory'] = function ($app) {
            return new ClassMetadataFactory(new StaticMethodLoader());
        };

        $app['validator.validator_factory'] = function () use ($app) {
            $validators = isset($app['validator.validator_service_ids']) ? $app['validator.validator_service_ids'] : array();

            return new ConstraintValidatorFactory($app, $validators);
        };

        $app['validator.object_initializers'] = function ($app) {
            return array();
        };
    }
}
