<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Vladislav Rastrusny aka FractalizeR <FractalizeR@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Extension;

use Silex\Application;
use Silex\ExtensionInterface;

class SmartyExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        $app['smarty'] = $app->share(function () use ($app)
        {
            if (!isset ($app['smarty.dir'])) {
                throw new \RuntimeException("'smarty.dir' is not defined. Please provide this option to Application->register call.");
            }

            require_once($app['smarty.dir'] . '/libs/Smarty.class.php');
            $smarty = new \Smarty();

            if (isset($app["smarty.options"])) {
                foreach ($app["smarty.options"] as $smartyOptionName => $smartyOptionValue) {
                    $smarty->$smartyOptionName = $smartyOptionValue;
                }
            }

            $smarty->assign("app", $app);

            if (isset($app['smarty.configure'])) {
                $app['smarty.configure']($smarty);
            }

            return $smarty;
        });
    }
}
