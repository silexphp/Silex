<?php
/*
 * This file is part of the Silex framework.
 *
 * (c) Vladislav Rastrusny aka FractalizeR <FractalizeR@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Tests\Extension;

use Silex\Application;
use Silex\Extension\SmartyExtension;

use Symfony\Component\HttpFoundation\Request;

/**
 * SmartyExtension test cases
 *
 * Smarty 3.0 demo files should be corrected for tests to pass when PHPUnit is run in CLI mode:
 *
 *   - delete line with {popup_init} from Smarty\demo\templates\header.tpl (should be done already from Smarty 3.0.8 onwards)
 *   - delete lines with SERVER_NAME from Smarty\demo\templates\index.tpl (request is pending http://www.smarty.net/forums/viewtopic.php?t=19132)
 *
 * @author Vladislav Rastrusny aka FractalizeR <FractalizeR@yandex.ru>
 */
class SmartyExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string Path to smarty distribution
     */
    private $smartyPath;

    public function setUp()
    {
        $this->smartyPath = __DIR__ . '/../../../../vendor/Smarty';

        if (!is_dir($this->smartyPath)) {
            $this->markTestSkipped('Smarty submodule was not installed.');
        }
    }

    public function testRegisterAndRender()
    {
        $app = new Application();

        $app->register(new SmartyExtension(),
                       array(
                            'smarty.dir' => $this->smartyPath,
                            'smarty.options' => array('template_dir' => $this->smartyPath . '/demo/templates',
                                                      'compile_dir' => $this->smartyPath . '/demo/templates_c',
                                                      'config_dir' => $this->smartyPath . '/demo/configs',
                                                      'cache_dir' => $this->smartyPath . '/demo/cache',),
                       ));

        $app->get('/hello', function() use ($app)
        {
            $smarty = $app['smarty'];
            $smarty->debugging = false;
            $smarty->caching = true;
            $smarty->cache_lifetime = 120;

            $smarty->assign("Name", "Fred Irving Johnathan Bradley Peppergill", true);
            $smarty->assign("FirstName", array("John", "Mary", "James", "Henry"));
            $smarty->assign("LastName", array("Doe", "Smith", "Johnson", "Case"));
            $smarty->assign("Class", array(array("A", "B", "C", "D"), array("E", "F", "G", "H"),
                                          array("I", "J", "K", "L"), array("M", "N", "O", "P")));

            $smarty->assign("contacts", array(array("phone" => "1", "fax" => "2", "cell" => "3"),
                                             array("phone" => "555-4444", "fax" => "555-3333", "cell" => "760-1234")));

            $smarty->assign("option_values", array("NY", "NE", "KS", "IA", "OK", "TX"));
            $smarty->assign("option_output", array("New York", "Nebraska", "Kansas", "Iowa", "Oklahoma", "Texas"));
            $smarty->assign("option_selected", "NE");

            return $smarty->fetch('index.tpl');
        });

        $request = Request::create('/hello');

        $response = $app->handle($request);
        $this->assertGreaterThan(7000, strlen($response->getContent()));
    }
}

