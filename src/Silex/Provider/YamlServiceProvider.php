<?php

namespace Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml;

class YamlServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $parser = new Yaml\Yaml();
        $app['yaml'] = $app->share(function () use ($parser) {
                return $parser;
            }
        );
    }

    public function boot(Application $app)
    {
    }

}