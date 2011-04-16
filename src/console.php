<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Silex\Application;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$console = new ConsoleApplication('Silex', Application::VERSION);
$console
    ->register('update')
    ->setDescription('Update silex.phar to the latest release.')
    ->setHelp(<<<EOF
The <info>update</info> command updates the silex.phar file to the latest version of Silex.
EOF
    )
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $remoteFilename = 'http://silex-project.org/get/silex.phar';
        $localFilename = getcwd().'/silex.phar';

        file_put_contents($localFilename, file_get_contents($remoteFilename));
    })
;

return $console;
