<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Translation;

use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\MessageSelector;

/**
 * Translator that gets the current locale from the container.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Translator extends BaseTranslator
{
    protected $app;

    public function __construct(\Pimple $app, MessageSelector $selector)
    {
        $this->app = $app;

        parent::__construct(null, $selector);
    }

    public function getLocale()
    {
        return $this->app['locale'];
    }
}
