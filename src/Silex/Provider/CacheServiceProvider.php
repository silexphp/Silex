<?php

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Symfony Cache component Provider.
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['cache.directory'] = null;
        $container['cache.namespace_prefix'] = null;

        $container['cache.system.namespace'] = 'system';
        $container['cache.system.default_lifetime'] = 0;
        $container['cache.system.version'] = null;

        $container['cache.app.namespace'] = 'app';
        $container['cache.app.default_lifetime'] = 0;
        $container['cache.app.dsn'] = null;
        $container['cache.app.connection_options'] = array();

        $container['cache.logger'] = function ($container) {
            return $container['logger'];
        };

        $container['cache.pools.options'] = array();

        $container['cache.pools'] = function ($container) {
            if (!$container['cache.directory']) {
                throw new \LogicException('You must set the cache.directory parameter to the location of your cache root directory.');
            }

            $pools = new Container();

            $pools['system'] = function () use ($container) {
                return $container['cache.system.factory']($container['cache.system.namespace']);
            };
            $pools['app'] = function () use ($container) {
                return $container['cache.app.factory']($container['cache.app.namespace']);
            };

            foreach ($container['cache.pools.options'] as $name => $options) {
                if ($name === 'system' || $name === 'app') {
                    throw new \LogicException('A cache pool cannot be named system or app.');
                }

                if (is_string($options)) {
                    $options = array('adapter' => $options);
                } elseif (!isset($options['adapter'])) {
                    throw new \LogicException(sprintf('Invalid definition specified for the cache pool %s. You must specify an adapter.', $name));
                }

                if ($options['adapter'] !== 'app' && $options['adapter'] !== 'system') {
                    throw new \LogicException(sprintf('Invalid adapter specified for the cache pool %s. Expected app or system, got %s.', $name, is_object($options['adapter']) ? get_class($options['adapter']) : var_export($options['adapter'], true)));
                }

                $pools[$name] = function () use ($container, $name, $options) {
                    return $container['cache.'.$options['adapter'].'.factory'](
                        isset($options['namespace']) ? $options['namespace'] : $container['cache.'.$options['adapter'].'.namespace'].'_'.$name,
                        isset($options['lifetime']) ? $options['lifetime'] : null
                    );
                };
            }

            return $pools;
        };

        $container['cache.system.factory'] = $container->protect(function ($namespace, $lifetime = null) use ($container) {
            return AbstractAdapter::createSystemCache(
                $container['cache.namespace_prefix'].$namespace,
                $lifetime !== null ? $lifetime : $container['cache.system.default_lifetime'],
                $container['cache.system.version'],
                $container['cache.directory'],
                $container['cache.logger']
            );
        });

        $container['cache.app.provider'] = function ($container) {
            if (null !== $dsn = $container['cache.app.dsn']) {
                // Use the AbstractAdapter method on 3.3+
                return is_callable(AbstractAdapter::class, 'createConnection') ?
                    AbstractAdapter::createConnection($dsn, $container['cache.app.connection_options']) :
                    RedisAdapter::createConnection($dsn, $container['cache.app.connection_options']);
            }

            return null;
        };

        $container['cache.app.factory'] = $container->protect(function ($namespace, $lifetime = null) use ($container) {
            $namespace = $container['cache.namespace_prefix'].$namespace;
            if (null === $lifetime) {
                $lifetime = $container['cache.app.default_lifetime'];
            }

            if (null !== $provider = $container['cache.app.provider']) {
                switch (true) {
                    case 0 === strpos($container['cache.app.dsn'], 'redis://'):
                        $adapter = new RedisAdapter($provider, $namespace, $lifetime);
                        break;

                    case 0 === strpos($container['cache.app.dsn'], 'memcached://'):
                        $adapter = new MemcachedAdapter($provider, $namespace, $lifetime);
                        break;

                    default:
                        throw new \LogicException('Invalid cache DSN. CacheServiceProvider only supports Redis and Memcached providers.');
                }
            } else {
                $adapter = new FilesystemAdapter($namespace, $lifetime, $container['cache.directory']);
            }

            if (null !== $logger = $container['cache.logger']) {
                $adapter->setLogger($logger);
            }

            return $adapter;
        });
    }
}
