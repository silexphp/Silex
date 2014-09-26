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

use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\MessageSelector;

/**
 * Translator that gets the current locale from the Silex application.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Translator extends BaseTranslator
{
    protected $app;

    public function __construct(Application $app, MessageSelector $selector)
    {
        $this->app = $app;

        parent::__construct(null, $selector);
    }

    public function getLocale()
    {
        return $this->app['locale'];
    }

    public function setLocale($locale)
    {
        if (null === $locale) {
            return;
        }

        $this->app['locale'] = $locale;

        parent::setLocale($locale);
    }
}
