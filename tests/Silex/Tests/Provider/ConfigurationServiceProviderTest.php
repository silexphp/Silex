<?php

namespace Silex\Tests\Provider
{
    use Silex\Application;
    use Silex\Provider\ConfigurationServiceProvider;

class ConfigurationServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Silex\Application
     */
    private $app;

    protected function setUp()
    {
        $this->app = new Application();
    }

    /**
     *
     */
    public function testIni()
    {
        $filename = __DIR__ . '/fixtures/configuration.ini';
        $this->app->register(new ConfigurationServiceProvider($filename));

        $this->assertInstanceOf('Acme\ServiceProvider\MyServiceProvider', $this->app['acme']);
        $this->assertInternalType('array', $this->app['config']);
    }

    public function testJson()
    {
        $filename = __DIR__ . '/fixtures/configuration.json';
        $this->app->register(new ConfigurationServiceProvider($filename));

        $this->assertInstanceOf('Acme\ServiceProvider\MyServiceProvider', $this->app['acme']);
        $this->assertInternalType('array', $this->app['config']);
    }
}
}

namespace Acme\ServiceProvider
{
    use Silex\ServiceProviderInterface;
    use Silex\Application;

    class MyServiceProvider implements ServiceProviderInterface
    {
        public function register(Application $app)
        {
            $app['acme'] = $this;
        }
    }
}

namespace Acme
{
    use Silex\ControllerCollection;
	use Silex\ControllerProviderInterface;
    use Silex\Application;

    class BlogControllerProvider implements ControllerProviderInterface
    {
        public function connect(Application $app)
        {
            $controllers = new ControllerCollection();
            return $controllers;
        }
    }
}