<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Application;

/**
 * Translation trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait TranslationTrait
{
    public function trans($id, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        return $app['translator']->trans($id, $parameters, $domain, $locale);
    }

    public function transChoice($id, $number, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        return $app['translator']->transChoice($id, $number, $parameters, $domain, $locale);
    }
}
