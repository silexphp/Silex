<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * The Compiler class compiles the Silex framework.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Compiler
{
    public function compile($pharFile = 'silex.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, 'Silex');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->in(__DIR__.'/..')
            ->in(__DIR__.'/../../vendor/Symfony/Component/ClassLoader')
            ->in(__DIR__.'/../../vendor/Symfony/Component/EventDispatcher')
            ->in(__DIR__.'/../../vendor/Symfony/Component/HttpFoundation')
            ->in(__DIR__.'/../../vendor/Symfony/Component/HttpKernel')
            ->in(__DIR__.'/../../vendor/Symfony/Component/Routing')
            ->in(__DIR__.'/../../vendor/Symfony/Component/BrowserKit')
            ->in(__DIR__.'/../../vendor/Symfony/Component/CssSelector')
            ->in(__DIR__.'/../../vendor/Symfony/Component/DomCrawler')
        ;

        foreach ($finder as $file) {
            $path = str_replace(realpath(__DIR__.'/../..').'/', '', $file->getRealPath());
            $content = Kernel::stripComments(file_get_contents($file));

            $phar->addFromString($path, $content);
        }

        // Stubs
        $phar['_cli_stub.php'] = $this->getStub();
        $phar['_web_stub.php'] = $this->getStub();
        $phar->setDefaultStub('_cli_stub.php', '_web_stub.php');

        $phar->stopBuffering();

        // $phar->compressFiles(\Phar::GZ);

        unset($phar);
    }

    protected function getStub()
    {
        return <<<EOF
<?php
/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require_once __DIR__.'/autoload.php';

__HALT_COMPILER();
EOF;
    }
}
