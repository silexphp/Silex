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

use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security trait.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait SecurityTrait
{
    /**
     * Encodes the raw password.
     *
     * @param UserInterface $user     A UserInterface instance
     * @param string        $password The password to encode
     *
     * @return string The encoded password
     *
     * @throws \RuntimeException when no password encoder could be found for the user
     */
    public function encodePassword(UserInterface $user, $password)
    {
        return $this['security.encoder_factory']->getEncoder($user)->encodePassword($password, $user->getSalt());
    }

    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param mixed $attributes
     * @param mixed $object
     *
     * @return bool
     *
     * @throws AuthenticationCredentialsNotFoundException when the token storage has no authentication token
     */
    public function isGranted($attributes, $object = null)
    {
        return $this['security.authorization_checker']->isGranted($attributes, $object);
    }
}
