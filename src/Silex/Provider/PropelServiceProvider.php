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

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Propel service provider.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PropelServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $path = isset($app['propel.path']) ? $app['propel.path'].'/Propel.php' : realpath('./vendor/propel/runtime/lib/Propel.php');
        $modelPath = isset($app['propel.model_path']) ? $app['propel.model_path'] : realpath('./build/classes');
        $internalAutoload = isset($app['propel.internal_autoload']) ? $app['propel.internal_autoload'] : false;

        if (isset($app['propel.config_file'])) {
            $config = $app['propel.config_file'];
        } else {
            $currentDir = getcwd();
            if (!@chdir(realpath('./build/conf'))) {
                throw new \InvalidArgumentException(__CLASS__.': please, initialize the "propel.config_file" property.');
            }

            $files = glob('classmap*.php');
            if (!$files || empty($files)) {
                throw new \InvalidArgumentException(__CLASS__.': please, initialize the "propel.config_file" property.');
            }

            $config = '/build/conf/'.substr(strstr($files[0], '-'), 1);
            chdir($currentDir);
        }

        if ($internalAutoload) {
            set_include_path($modelPath.PATH_SEPARATOR.get_include_path());
        } else {
            $app['autoloader']->registerNamespace('model', $modelPath);
        }

        if (!class_exists('Propel')) {
            require $path;
        }

        \Propel::init($config);
    }
}
