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

use DateTime;
use Doctrine\Common\Annotations\AnnotationReader;
use JMS\SerializerBundle\Serializer\Serializer;
use JMS\SerializerBundle\Serializer\Construction\UnserializeObjectConstructor;
use JMS\SerializerBundle\Metadata\Driver\AnnotationDriver;
use JMS\SerializerBundle\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\SerializerBundle\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\SerializerBundle\Serializer\Handler\ArrayCollectionHandler;
use JMS\SerializerBundle\Serializer\Handler\ConstraintViolationHandler;
use JMS\SerializerBundle\Serializer\Handler\DateTimeHandler;
use JMS\SerializerBundle\Serializer\Handler\DoctrineProxyHandler;
use JMS\SerializerBundle\Serializer\Handler\FormErrorHandler;
use JMS\SerializerBundle\Serializer\Handler\ObjectBasedCustomHandler;
use JMS\SerializerBundle\Serializer\JsonDeserializationVisitor;
use JMS\SerializerBundle\Serializer\JsonSerializationVisitor;
use JMS\SerializerBundle\Serializer\XmlDeserializationVisitor;
use JMS\SerializerBundle\Serializer\XmlSerializationVisitor;
use JMS\SerializerBundle\Serializer\YamlSerializationVisitor;
use Metadata\MetadataFactory;
use Metadata\Cache\FileCache;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * JMS Serializer Bundle integration for Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Marijn Huizendveld <marijn@pink-tie.com>
 */
class JMSSerializerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['serializer.naming_strategy.seperator'] = '_';
        $app['serializer.naming_strategy.lower_case'] = true;
        $app['serializer.date_time_handler.format'] = DateTime::ISO8601;
        $app['serializer.date_time_handler.default_timezone'] = 'UTC';
        $app['serializer.disable_external_entities'] = true;

        $app['serializer.metatadata.cache'] = $app->share(function () use ($app) {
            return new FileCache($app['serializer.cache.directory']);
        });
        
        $app['serializer.metadata.annotation_reader'] = $app->share(function () use ($app) {
            return new AnnotationReader();
        });
        
        $app['serializer.metadata.annotation_driver'] = $app->share(function () use ($app) {
            return new AnnotationDriver($app['serializer.metadata.annotation_reader']);
        });

        $app['serializer.naming_strategy'] = $app->share(function () use ($app) {
            $seperator = $app['serializer.naming_strategy.seperator'];
            $lowerCase = $app['serializer.naming_strategy.lower_case'];

            return new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy($seperator, $lowerCase));
        });

        $app['serializer.custom_handlers.array_collection_handler'] = $app->share(function () use ($app) {
            return new ArrayCollectionHandler();
        });

        $app['serializer.custom_handlers.date_time_handler'] = $app->share(function () use ($app) {
            $format = $app['serializer.date_time_handler.format'];
            $defaultTimezone = $app['serializer.date_time_handler.default_timezone'];

            return new DateTimeHandler($format, $defaultTimezone);
        });

        $app['serializer.custom_handlers.doctrine_proxy_handler'] = $app->share(function () use ($app) {
            return new DoctrineProxyHandler();
        });

        $app['serializer.custom_handlers.object_based_custom_handler'] = $app->share(function () use ($app) {
            return new ObjectBasedCustomHandler($app['serializer.object_constructor'], $app['serializer.metadata_factory']);
        });

        if (class_exists('Symfony\Component\Validator\ConstraintViolation')) {
            $app['serializer.custom_handlers.constraing_violation_handler'] = $app->share(function () use ($app) {
                return new ConstraintViolationHandler();
            });
        }

        if (isset($app['translator'])) {
            $app['serializer.custom_handlers.form_error_handler'] = $app->share(function () use ($app) {
                return new FormErrorHandler($app['translator']);
            });
        }

        $app['serializer.serialization.custom_handlers'] = $app->share(function () use ($app) {
            $handlers = array(
                $app['serializer.custom_handlers.date_time_handler'],
                $app['serializer.custom_handlers.doctrine_proxy_handler'],
                $app['serializer.custom_handlers.object_based_custom_handler']
            );

            if (isset($app['serializer.custom_handlers.constraing_violation_handler'])) {
                $handlers[] = $app['serializer.custom_handlers.constraing_violation_handler'];
            }

            if (isset($app['serializer.custom_handlers.form_error_handler'])) {
                $handlers[] = $app['serializer.custom_handlers.form_error_handler'];
            }

            return $handlers;
        });

        $app['serializer.deserialization.custom_handlers'] = $app->share(function () use ($app) {
            return array(
                $app['serializer.custom_handlers.array_collection_handler'],
                $app['serializer.custom_handlers.date_time_handler'],
                $app['serializer.custom_handlers.object_based_custom_handler']
            );
        });

        $app['serializer.object_constructor'] = $app->share(function () use ($app) {
            return new UnserializeObjectConstructor();
        });

        $app['serializer.metadata_factory'] = $app->share(function () use ($app) {
            $factory = new MetadataFactory(
                $app['serializer.metadata.annotation_driver'],
                'Metadata\ClassHierarchyMetadata'
            );

            $factory->setCache($app['serializer.metatadata.cache']);

            return $factory;
        });

        $app['serializer.serialization_visitors'] = $app->share(function () use ($app) {
            $namingStrategy = $app['serializer.naming_strategy'];
            $customHandlers = $app['serializer.serialization.custom_handlers'];

            return array(
                "json" => new JsonSerializationVisitor($namingStrategy, $customHandlers),
                "xml" => new XmlSerializationVisitor($namingStrategy, $customHandlers),
                "yaml" => new YamlSerializationVisitor($namingStrategy, $customHandlers)
            );
        });

        $app['serializer.deserialization_visitors'] = $app->share(function () use ($app) {
            $namingStrategy = $app['serializer.naming_strategy'];
            $customHandlers = $app['serializer.deserialization.custom_handlers'];
            $objectConstructor = $app['serializer.object_constructor'];
            $disableExternalEntities = $app['serializer.disable_external_entities'];

            return array(
                "json" => new JsonDeserializationVisitor($namingStrategy, $customHandlers, $objectConstructor),
                "xml" => new XmlDeserializationVisitor($namingStrategy, $customHandlers, $objectConstructor, $disableExternalEntities)
            );
        });

        $app['serializer'] = $app->share(function () use ($app) {
            return new Serializer(
                $app['serializer.metadata_factory'],
                $app['serializer.serialization_visitors'],
                $app['serializer.deserialization_visitors']
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
