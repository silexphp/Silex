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

/**
 * Symfony Validator component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ValidatorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['validator.builder'] = function ($app) {
            $builder = new ValidatorBuilder();
            $builder
                ->addMethodMapping('loadValidatorMetadata')
                ->setConstraintValidatorFactory($app['validator.validator_factory'])
            ;

            if (isset($app['translator'])) {
                $builder->setTranslator($app['translator']);
            }

            return $builder;
        };

        $app['validator'] = function ($app) {
            return $app['validator.builder']->getValidator();
        };

        $app['validator.validator_factory'] = function () use ($app) {
            $validators = isset($app['validator.validator_service_ids']) ? $app['validator.validator_service_ids'] : array();

            return new ConstraintValidatorFactory($app, $validators);
        };
    }
}
