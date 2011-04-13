<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Extension;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration as DBALConfiguration;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration as ORMConfiguration;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;

use Silex\Application;
use Silex\ExtensionInterface;

class DoctrineExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        // @TODO should we throw an Exception if orm is activated but not dbal ?
        if (isset($app['doctrine.dbal.connection_options'])) {
            $this->loadDoctrineConfiguration($app);
            $this->loadDoctrineDbal($app);
            if (isset($app['doctrine.orm']) and true === $app['doctrine.orm']) {
                $this->setOrmDefaults($app);
                $this->loadDoctrineOrm($app);
            }
        }

        foreach(array('Common', 'DBAL', 'ORM') as $vendor) {
            $key = sprintf('doctrine.%s.class_path', strtolower($vendor));
            if (isset($app[$key])) {
                $app['autoloader']->registerNamespace(sprintf('Doctrine\%s', $vendor), $app[$key]);
            }
        }
    }

    private function loadDoctrineDbal(Application $app)
    {
        $app['doctrine.dbal.event_manager'] = $app->share(function() {
            $eventManager = new EventManager;

            return $eventManager;
        });

        $app['doctrine.dbal.connection'] = $app->share(function() use($app) {

            if (!isset($app['doctrine.dbal.connection_options'])) {
                throw new \InvalidArgumentException('The "doctrine.orm.connection_options" parameter must be defined');
            }
            $config = $app['doctrine.configuration'];
            $eventManager = $app['doctrine.dbal.event_manager'];
            $conn = DriverManager::getConnection($app['doctrine.dbal.connection_options'], $config, $eventManager);

            return $conn;
        });
    }

    private function loadDoctrineOrm(Application $app)
    {
        $self = $this;
        $app['doctrine.orm.em'] = $app->share(function() use($self, $app) {

            $connection = $app['doctrine.dbal.connection'];
            $config = $app['doctrine.configuration'];
            $em = EntityManager::create($connection, $config);

            return $em;
        });
    }

    private function setOrmDefaults(Application $app)
    {
        $defaults = array(
            'entities' => array(
                array('type' => 'annotation', 'path' => 'Entity', 'namespace' => 'Entity')
            ),
            'proxies_dir' => 'cache/doctrine/Proxy',
            'proxies_namespace' => 'DoctrineProxy',
            'auto_generate_proxies' => true,
        );
        foreach($defaults as $key => $value) {
            if (!isset($app['doctrine.orm.'.$key])) {
                $app['doctrine.orm.'.$key] = $value;
            }
        }
    }

    public function loadDoctrineConfiguration(Application $app)
    {
        $app['doctrine.configuration'] = $app->share(function() use($app) {

            if (isset($app['doctrine.orm']) and true === $app['doctrine.orm']) {
                $config = new ORMConfiguration;
                $cache = new ApcCache;
                $config->setMetadataCacheImpl($cache);
                $config->setQueryCacheImpl($cache);

                $chain = new DriverChain;
                foreach((array)$app['doctrine.orm.entities'] as $entity) {
                    switch($entity['type']) {
                        case 'annotation':
                            $reader = new AnnotationReader;
                            $reader->setAnnotationNamespaceAlias('Doctrine\\ORM\\Mapping\\', 'orm');
                            $driver = new AnnotationDriver($reader, (array)$entity['path']);
                            $chain->addDriver($driver, $entity['namespace']);
                            break;
                        case 'yml':
                            $driver = new YamlDriver((array)$entity['path']);
                            $driver->setFileExtension('.yml');
                            $chain->addDriver($driver, $entity['namespace']);
                            break;
                        case 'xml':
                            $driver = new XmlDriver((array)$entity['path'], $entity['namespace']);
                            $driver->setFileExtension('.xml');
                            $chain->addDriver($driver, $entity['namespace']);
                            break;
                        default:
                            throw new \InvalidArgumentException(sprintf('"%s" is not a recognized driver', $type));
                            break;
                    }
                }
                $config->setMetadataDriverImpl($chain);

                $config->setProxyDir($app['doctrine.orm.proxies_dir']);
                $config->setProxyNamespace($app['doctrine.orm.proxies_namespace']);
                $config->setAutoGenerateProxyClasses($app['doctrine.orm.auto_generate_proxies']);
            }
            else {
                $config = new DBALConfiguration;
            }

            return $config;
        });
    }
}

