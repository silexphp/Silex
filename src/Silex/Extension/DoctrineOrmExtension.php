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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;

use Silex\Application;
use Silex\ExtensionInterface;

class DoctrineOrmExtension implements ExtensionInterface
{
    public function register(Application $app)
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

        $self = $this;
        $app['doctrine.orm.entity_manager'] = $app->share(function() use($app, $self) {

            if (!isset($app['doctrine.orm.connection_options'])) {
                throw new \InvalidArgumentException('The "doctrine.orm.connection_options" parameter must be defined');
            }
            $connectionOptions = $app['doctrine.orm.connection_options'];
            $config = $self->getConfig($app);
            $em = EntityManager::create($connectionOptions, $config);

            return $em;
        });

        foreach(array('Common', 'DBAL') as $vendor) {
            $key = sprintf('doctrine.%s.class_path', strtolower($vendor));
            if (isset($app[$key])) {
                $app['autoloader']->registerNamespace(sprintf('Doctrine\%s', $vendor), $app[$key]);
            }
        }
        if (isset($app['doctrine.orm.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\ORM', $app['doctrine.orm.class_path']);
        }
    }

    public function getConfig(Application $app)
    {
        $config = new Configuration;

        $cache = new ApcCache;
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        $chain = new DriverChain();
        foreach((array)$app['doctrine.orm.entities'] as $entity) {
            switch($entity['type']) {
                case 'annotation':
                    $reader = new AnnotationReader();
                    $reader->setAnnotationNamespaceAlias('Doctrine\\ORM\\Mapping\\', 'orm');
                    $chain->addDriver(new AnnotationDriver($reader, (array)$entity['path']), $entity['namespace']);
                    break;
                case 'yml':
                    $driver = new YamlDriver((array)$entity['path']);
                    $driver->setFileExtension('.yml');
                    $chain->addDriver($driver);
                    break;
                case 'xml':
                    $chain->addDriver(new XmlDriver((array)$entity['path']));
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

        return $config;
    }
}

