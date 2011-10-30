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
 * Propel ORM service provider.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PropelServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $propelPath = isset($app['propel.path']) ? $app['propel.path'].'/Propel.php' : realpath('.').'/vendor/propel/runtime/lib/Propel.php';
        $propelModelPath = isset($app['propel.model_path']) ? $app['propel.model_path'] : realpath('.').'/build/classes';
        
        if (isset($app['propel.config_file'])) {
            $propelConfig = $app['propel.config_file'];
        }
        else {
            $currentDir = getcwd();
            chdir(realpath('.').'/build/conf');
            $files = glob('classmap*.*');
            $propelConfig = '/build/conf/'.substr(strstr($files[0], '-'), 1); 
            chdir($currentDir);            
        }
        
        require $propelPath;
        \Propel::init($propelConfig);
        set_include_path($propelModelPath.PATH_SEPARATOR.get_include_path());
    }
}
