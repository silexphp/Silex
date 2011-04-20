<?php

namespace Silex\Extension;

use Silex\Application;
use Silex\ExtensionInterface;
use Silex\Extension\DoctrineExtension;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;

class DoctrineORMExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app->register(new DoctrineExtension());

        $app['em'] = $app->share(function () use ($app) {
            return EntityManager::create($app['db'], $app['em.config'], $app['db.event_manager']);
        });

        $app['em.config'] = $app->share(function () use ($app) {
            $config = new Configuration();

            if (isset($app['em.entities_path'])) {
                $driverImpl = $config->newDefaultAnnotationDriver($app['em.entities_path']);
                $config->setMetadataDriverImpl($driverImpl);
            } else if (isset($app['em.metadata_driver'])) {
                $config->setMetadataDriverImpl($app['em.metadata_driver']);
            }

            if (isset($app['em.proxy_dir'])) {
                $config->setProxyDir($app['em.proxy_dir']);
            }

            if (isset($app['em.proxy_namespace'])) {
                $config->setProxyNamespace($app['em.proxy_namespace']);
            }

            return $config;
        });

        if (isset($app['db.orm.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\ORM', $app['db.orm.class_path']);
        }
    }
}
