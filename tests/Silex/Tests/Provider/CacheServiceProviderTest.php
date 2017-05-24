<?php

namespace Silex\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\Provider\CacheServiceProvider;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * CacheServiceProvider tests.
 */
class CacheServiceProviderTest extends TestCase
{
    private static $cacheDir;
    private static $redisHost;
    private static $memcachedHost;
    /**
     * @var \Redis|null
     */
    private static $redis;
    /**
     * @var \Memcached|null
     */
    private static $memcached;

    public static function setUpBeforeClass()
    {
        if (!class_exists(AbstractAdapter::class)) {
            self::markTestSkipped('Symfony cache component is required.');
        }

        self::$cacheDir = sys_get_temp_dir().'/silex-cache';
        self::$redisHost = getenv('REDIS_HOST');
        self::$memcachedHost = getenv('MEMCACHED_HOST');

        if (extension_loaded('redis')) {
            $redis = new \Redis();
            if (@$redis->connect(self::$redisHost)) {
                self::$redis = $redis;
            }
        }
        if (class_exists(MemcachedAdapter::class) && MemcachedAdapter::isSupported()) {
            $memcached = AbstractAdapter::createConnection('memcached://'.self::$memcachedHost, array('binary_protocol' => false));
            $memcached->get('foo');
            $code = $memcached->getResultCode();

            if (\Memcached::RES_SUCCESS === $code || \Memcached::RES_NOTFOUND === $code) {
                self::$memcached = $memcached;
            }
        }
    }

    public static function tearDownAfterClass()
    {
        self::rmdir(self::$cacheDir);

        if (null !== self::$redis) {
            self::$redis->flushDB();
        }

        if (null !== self::$memcached) {
            self::$memcached->flush();
        }
    }

    public static function skipIfApcuIsAvailable()
    {
        if (ApcuAdapter::isSupported()) {
            self::markTestSkipped('Extension APCu is enabled.');
        }
    }

    public static function skipIfApcuIsNotAvailable()
    {
        if (!ApcuAdapter::isSupported()) {
            self::markTestSkipped('Extension APCu is required.');
        }
    }

    public static function skipIfRedisIsNotAvailable()
    {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('Extension redis is required.');
        }
        if (null === self::$redis) {
            self::markTestSkipped('Connection to redis server is required.');
        }
    }

    public static function skipIfMemcachedIsNotAvailable()
    {
        if (!class_exists(MemcachedAdapter::class)) {
            self::markTestSkipped('Symfony cache component >= 3.3.0 is required.');
        }
        if (!MemcachedAdapter::isSupported()) {
            self::markTestSkipped('Extension memcached >=2.2.0 is required.');
        }
        if (null === self::$memcached) {
            self::markTestSkipped('Connection to memcached server is required.');
        }
    }

    public static function rmdir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        if (!$dir || 0 !== strpos(dirname($dir), sys_get_temp_dir())) {
            throw new \Exception(__METHOD__."() operates only on subdirs of system's temp dir");
        }
        $children = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($children as $child) {
            if ($child->isDir()) {
                rmdir($child);
            } else {
                unlink($child);
            }
        }
        rmdir($dir);
    }

    public function testSystemPoolWithApcu()
    {
        self::skipIfApcuIsNotAvailable();

        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.system.namespace' => 'foo',
            'cache.system.version' => 'test-version',
        ));

        $pool1 = $app['cache.pools']['system'];

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $apcu = new ApcuAdapter('test_foo', 0, 'test-version');

        $item2 = $apcu->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());

        $fs = new FilesystemAdapter('test_foo', 0, self::$cacheDir);

        $item3 = $fs->getItem(__FUNCTION__);
        $this->assertTrue($item3->isHit());
        $this->assertSame('bar', $item3->get());
    }

    public function testSystemPoolWithoutApcu()
    {
        self::skipIfApcuIsAvailable();

        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.system.namespace' => 'foo',
        ));

        $pool1 = $app['cache.pools']['system'];

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        if (class_exists(PhpFilesAdapter::class) && PhpFilesAdapter::isSupported()) {
            // symfony/cache 3.2+ uses a PhpFilesAdapter if possible
            $pool2 = new PhpFilesAdapter('test_foo', 0, self::$cacheDir);
        } else {
            $pool2 = new FilesystemAdapter('test_foo', 0, self::$cacheDir);
        }

        $item2 = $pool2->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    public function testRedisAppPool()
    {
        self::skipIfRedisIsNotAvailable();

        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => str_replace('\\', '.', __CLASS__),
            'cache.app.dsn' => 'redis://'.self::$redisHost,
        ));

        $pool1 = $app['cache.pools']['app'];

        $this->assertInstanceOf(RedisAdapter::class, $pool1);

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $pool2 = new RedisAdapter(self::$redis, 'test_'.str_replace('\\', '.', __CLASS__), 0);

        $item2 = $pool2->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    /**
     * Checks if cache.app.connection_options is passed to createConnection().
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage is not a subclass of "Redis" or "Predis\Client"
     */
    public function testRedisAppPoolFailsIfInvalidConnectionOptions()
    {
        self::skipIfRedisIsNotAvailable();

        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.app.dsn' => 'redis://'.self::$redisHost,
            'cache.app.connection_options' => array('class' => __CLASS__),
        ));

        $pool1 = $app['cache.pools']['app'];
    }

    public function testMemcachedAppPool()
    {
        self::skipIfMemcachedIsNotAvailable();

        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => str_replace('\\', '.', __CLASS__),
            'cache.app.dsn' => 'memcached://'.self::$memcachedHost,
        ));

        $pool1 = $app['cache.pools']['app'];

        $this->assertInstanceOf(MemcachedAdapter::class, $pool1);

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $pool2 = new MemcachedAdapter(self::$memcached, 'test_'.str_replace('\\', '.', __CLASS__), 0);

        $item2 = $pool2->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    public function testFilesystemAppPool()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => 'foo',
        ));

        $pool1 = $app['cache.pools']['app'];

        $this->assertInstanceOf(FilesystemAdapter::class, $pool1);

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $fs = new FilesystemAdapter('test_foo', 0, self::$cacheDir);

        $item2 = $fs->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    public function testCustomSystemPoolWithStringOptions()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.system.namespace' => 'foo',
            'cache.system.version' => __FUNCTION__,
            'cache.pools.options' => array(
                'testpool' => 'system',
            ),
        ));

        $this->assertTrue(isset($app['cache.pools']['testpool']));

        $pool1 = $app['cache.pools']['testpool'];

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $pool2 = AbstractAdapter::createSystemCache('test_foo_testpool', 0, __FUNCTION__, self::$cacheDir);

        $item2 = $pool2->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    public function testCustomSystemPoolWithArrayOptions()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.system.namespace' => 'foo',
            'cache.system.version' => __FUNCTION__,
            'cache.pools.options' => array(
                'testpool' => array(
                    'adapter' => 'system',
                    'namespace' => 'mypool',
                ),
            ),
        ));

        $this->assertTrue(isset($app['cache.pools']['testpool']));

        $pool1 = $app['cache.pools']['testpool'];

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $pool2 = AbstractAdapter::createSystemCache('test_mypool', 0, __FUNCTION__, self::$cacheDir);

        $item2 = $pool2->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    public function testCustomAppPoolWithStringOptions()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => 'foo',
            'cache.pools.options' => array(
                'testpool' => 'app',
            ),
        ));

        $this->assertTrue(isset($app['cache.pools']['testpool']));

        $pool1 = $app['cache.pools']['testpool'];

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $pool2 = new FilesystemAdapter('test_foo_testpool', 0, self::$cacheDir);

        $item2 = $pool2->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    public function testCustomAppPoolWithArrayOptions()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => 'foo',
            'cache.pools.options' => array(
                'testpool' => array(
                    'adapter' => 'app',
                    'namespace' => 'mypool',
                ),
            ),
        ));

        $this->assertTrue(isset($app['cache.pools']['testpool']));

        $pool1 = $app['cache.pools']['testpool'];

        $item1 = $pool1->getItem(__FUNCTION__);
        $this->assertFalse($item1->isHit());
        $this->assertTrue($pool1->save($item1->set('bar')));

        $pool2 = new FilesystemAdapter('test_mypool', 0, self::$cacheDir);

        $item2 = $pool2->getItem(__FUNCTION__);
        $this->assertTrue($item2->isHit());
        $this->assertSame('bar', $item2->get());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage You must set the cache.directory parameter to the location of your cache root directory
     */
    public function testFailIfCacheDirectoryIsMissing()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.system.namespace' => 'foo',
        ));

        $pool = $app['cache.pools']['system'];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage A cache pool cannot be named system or app
     */
    public function testFailIfCustomPoolIsNamedSystem()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.system.namespace' => 'foo',
            'cache.pools.options' => array(
                'system' => 'system',
            ),
        ));

        $pool = $app['cache.pools']['system'];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage A cache pool cannot be named system or app
     */
    public function testFailIfCustomPoolIsNamedApp()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test_',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => 'foo',
            'cache.pools.options' => array(
                'app' => 'app',
            ),
        ));

        $pool = $app['cache.pools']['app'];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid definition specified for the cache pool testpool. You must specify an adapter
     */
    public function testFailIfCustomPoolAdapterIsNotSpecified()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => 'foo',
            'cache.pools.options' => array(
                'testpool' => array('namespace' => 'foo'),
            ),
        ));

        $pool = $app['cache.pools']['testpool'];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid adapter specified for the cache pool testpool. Expected app or system, got 'invalid'
     */
    public function testFailIfCustomPoolHasInvalidAdapter()
    {
        $app = new Application();
        $app->register(new CacheServiceProvider(), array(
            'cache.namespace_prefix' => 'test',
            'cache.directory' => self::$cacheDir,
            'cache.app.namespace' => 'foo',
            'cache.pools.options' => array(
                'testpool' => 'invalid',
            ),
        ));

        $pool = $app['cache.pools']['testpool'];
    }
}
