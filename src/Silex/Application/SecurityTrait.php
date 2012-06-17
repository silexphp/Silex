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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Security trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait SecurityTrait
{
    /**
     * Gets a user from the Security Context.
     *
     * @return mixed
     *
     * @see TokenInterface::getUser()
     */
    public function user()
    {
        if (null === $token = $this['security']->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    public function encode($password, $salt = null)
    {
        return $this['security.encoder']->encodePassword($password, $salt);
    }
}
