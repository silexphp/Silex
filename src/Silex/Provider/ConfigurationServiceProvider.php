<?php

namespace Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Configuration service provider.
 *
 * @package ServiceProvider
 * @author Andrew Stephanoff <andrew.stephanoff@gmail.com>
 */
class ConfigurationServiceProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param string $filename
     * @throws \InvalidArgumentException File not readable or not exists
     */
    public function __construct($filename)
    {
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException("File '{$filename}' not readable or not exists");
        }

        $type = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ('ini' === $type) {
            $this->config = self::parseIniFile($filename);

        } else if ('json' === $type) {
            $this->config = self::parseJsonFile($filename);

        } else if ('xml' === $type) {
            $this->config = self::parseXmlFile($filename);

        } else if ('php' === $type) {
            $this->config = require $filename;

        } else {
            $this->config = array();
        }
    }

    /**
     * @param \Silex\Application $app
     */
    public function register(Application $app)
    {
        $app['config'] = $this->config;

        self::initializeApplication($app, $this->config);
    }

    /**
     * @param Application $app
     * @param array $config
     */
    private static function initializeApplication(Application $app, array $config)
    {
        if (!isset($config['silex'])) {
            return;
        }

        if (isset($config['silex']['autoloader'])) {
            self::processSectionAutoloader($app, $config['silex']['autoloader']);
        }

        if (isset($config['silex']['service_provider'])) {
            self::processSectionServiceProvider($app, $config['silex']['service_provider']);
        }

        if (isset($config['silex']['controller_provider'])) {
            self::processSectionControllerProvider($app, $config['silex']['controller_provider']);
        }
    }

    /**
     * Register namespaces and prefixes in autoloader.
     *
     * @param Application $app
     * @param array $config
     */
    private static function processSectionAutoloader(Application $app, array $config)
    {
        if (isset($config['register_namespace'])) {
            foreach ($config['register_namespace'] as $namespace) {
                $app['autoloader']->registerNamespace($namespace['namespace'], $namespace['path']);
            }
        }

        if (isset($config['register_prefix'])) {
            foreach ($config['register_prefix'] as $prefix) {
                $app['autoloader']->registerPrefix($prefix['prefix'], $prefix['path']);
            }
        }
    }

    /**
     * Register service providers.
     *
     * @param Application $app
     * @param array $config
     */
    private static function processSectionServiceProvider(Application $app, array $config)
    {
        foreach ($config as $section) {
            $provider = self::instantinate($section);
            $app->register($provider);
        }
    }

    /**
     * Register controller providers and mount them.
     *
     * @param Application $app
     * @param array $config
     */
    private static function processSectionControllerProvider(Application $app, array $config)
    {
        foreach ($config as $section) {
            $provider = self::instantinate($section);
            $app->mount($section['mount'], $provider);
        }
    }

    /**
     * @param array $config
     * @return object
     */
    private static function instantinate($config)
    {
        if (isset($config['constructor']) && $config['constructor']) {
            $reflection = new \ReflectionClass($config['classname']);
            $instance = $reflection->newInstanceArgs(array_values($config['constructor']));
        } else {
            $instance = new $config['classname'];
        }

        return $instance;
    }

    /**
     * @param string $filename
     * @return array
     */
    private static function parseIniFile($filename)
    {
        $config = parse_ini_file($filename, true);

        foreach (array_keys($config) as $name) {
            foreach ($config[$name] as $key => $value) {
                if (false === strpos($key, '.')) {
                    continue;
                }

                $chunks = explode('.', strtolower($key));
                $last = count($chunks) - 1;

                $c =& $config[$name];

                for ($i = 0; $i < $last; ++ $i) {
                    if (!isset($c[$chunks[$i]])) {
                        $c[$chunks[$i]] = array();
                    }
                    $c =& $c[$chunks[$i]];
                }

                $c[$chunks[$last]] = $value;
                unset($config[$name][$key]);
            }
        }

        return $config;
    }

    /**
     * @param string $filename
     * @return array
     */
    private static function parseJsonFile($filename)
    {
        if (null === ($config = json_decode(file_get_contents($filename), true))) {
            throw new \RuntimeException("Json file '{$filename}' mailformed");
        }
        return $config;
    }

    /**
     * @param string $filename
     * @return array
     */
    private static function parseXmlFile($filename)
    {
        $xml = simplexml_load_file($filename);
        return $xml;
    }
}
