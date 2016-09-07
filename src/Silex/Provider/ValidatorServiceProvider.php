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
use Symfony\Component\Validator\Validator as LegacyValidator;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Validator\RecursiveValidator;

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
            if (isset($app['translator'])) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validator');
                $file = dirname($r->getFilename()).'/Resources/translations/validators.'.$app['locale'].'.xlf';
                if (file_exists($file)) {
                    $app['translator']->addResource('xliff', $file, $app['locale'], 'validators');
                }
            }
            
            if (isset($app['use_refactorized_validator']) && $app['use_refactorized_validator'] === true) {
                if (!class_exists('Symfony\\Component\\Validator\\Validator\\RecursiveValidator')) {
                    throw new \LogicException('Your symfony version does not support the refactorized validator');
                }
                
                return new RecursiveValidator(
                    $app['validator.execution_context.factory'],
                    $app['validator.metadata.factory'],
                    $app['validator.validator_factory'],
                    $app['validator.object_initializers']
                );
            }

            return new LegacyValidator(
                $app['validator.mapping.class_metadata_factory'],
                $app['validator.validator_factory'],
                isset($app['translator']) ? $app['translator'] : new DefaultTranslator(),
                'validators',
                $app['validator.object_initializers']
            );
        });

        $app['validator.execution_context.factory'] = $app->share(function ($app) {
            if (!class_exists('Symfony\\Component\\Validator\\Context\\ExecutionContextFactory')) {
                throw new \LogicException('Your symfony version does not support the ExecutionContextFactory');
            }
            
            // according to the documentation, 
            // a validator should use the validators
            // domain, so we use it for the execution
            // context
            return new ExecutionContextFactory($app['translator'], 'validators');
        });
        
        $app['validator.metadata.factory'] = $app->share(function ($app) {
            if (!class_exists('Symfony\\Component\\Validator\\Mapping\\Factory\\LazyLoadingMetadataFactory')) {
                throw new \LogicException('Your symfony version does not support the LazyLoadingMetadataFactory');
            }
            
            return new LazyLoadingMetadataFactory(new StaticMethodLoader());
        });

        $app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) {
            return new ClassMetadataFactory(new StaticMethodLoader());
        });

        $app['validator.validator_factory'] = $app->share(function () use ($app) {
            $validators = isset($app['validator.validator_service_ids']) ? $app['validator.validator_service_ids'] : array();

            return new ConstraintValidatorFactory($app, $validators);
        });

        $app['validator.object_initializers'] = $app->share(function ($app) {
            return array();
        });
    }

    public function boot(Application $app)
    {
    }
}
