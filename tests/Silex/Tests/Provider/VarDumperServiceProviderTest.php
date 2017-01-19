<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use Silex\Application;
use Silex\Provider\VarDumperServiceProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author SpacePossum
 *
 * @internal
 */
final class VarDumperServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $env
     * @param string $expectedRegExp
     *
     * @dataProvider provideDumpCases
     */
    public function testDump($env, $expectedRegExp)
    {
        $output = '';
        $app = new Application();
        $app['debug'] = true;
        $app->register(
            new VarDumperServiceProvider(),
            array(
                'var_dumper.env' => $env,
                'var_dumper.dump_destination' => $app->protect(
                    function ($line, $depth, $indentPad) use (&$output) {
                        $output .= sprintf("%s|%d|%s\n", $line, $depth, $indentPad);
                    }
                ),
            )
        );

        $app->get('/', function () {
            dump(false);

            return '';
        });

        $request = Request::create('/');
        $app->handle($request);

        $this->assertRegExp($expectedRegExp, $output);
    }

    public function provideDumpCases()
    {
        $expectedRegExMessageCLI = '#^false\|0\|  [\n]\|-1\|  [\n]$#s';

        return array(
            array(null, $expectedRegExMessageCLI),
            array('cli', $expectedRegExMessageCLI),
            array('fpm-fcgi', '#<script> Sfdump.*#'),
        );
    }
}
