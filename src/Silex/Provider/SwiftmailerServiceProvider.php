<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Swiftmailer Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwiftmailerServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['swiftmailer.options'] = array();

        $app['mailer.initialized'] = false;

        $app['mailer'] = function ($app) {
            $app['mailer.initialized'] = true;

            return new \Swift_Mailer($app['swiftmailer.spooltransport']);
        };

        $app['swiftmailer.spooltransport'] = function ($app) {
            return new \Swift_SpoolTransport($app['swiftmailer.spool']);
        };

        $app['swiftmailer.spool'] = function ($app) {
            return new \Swift_MemorySpool();
        };

        $app['swiftmailer.transport'] = function ($app) {
            $transport = new \Swift_Transport_EsmtpTransport(
                $app['swiftmailer.transport.buffer'],
                array($app['swiftmailer.transport.authhandler']),
                $app['swiftmailer.transport.eventdispatcher']
            );

            $options = $app['swiftmailer.options'] = array_replace(array(
                'host'       => 'localhost',
                'port'       => 25,
                'username'   => '',
                'password'   => '',
                'encryption' => null,
                'auth_mode'  => null,
            ), $app['swiftmailer.options']);

            $transport->setHost($options['host']);
            $transport->setPort($options['port']);
            $transport->setEncryption($options['encryption']);
            $transport->setUsername($options['username']);
            $transport->setPassword($options['password']);
            $transport->setAuthMode($options['auth_mode']);

            return $transport;
        };

        $app['swiftmailer.transport.buffer'] = function () {
            return new \Swift_Transport_StreamBuffer(new \Swift_StreamFilters_StringReplacementFilterFactory());
        };

        $app['swiftmailer.transport.authhandler'] = function () {
            return new \Swift_Transport_Esmtp_AuthHandler(array(
                new \Swift_Transport_Esmtp_Auth_CramMd5Authenticator(),
                new \Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                new \Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
            ));
        };

        $app['swiftmailer.transport.eventdispatcher'] = function () {
            return new \Swift_Events_SimpleEventDispatcher();
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(KernelEvents::TERMINATE, function (PostResponseEvent $event) use ($app) {
            // To speed things up (by avoiding Swift Mailer initialization), flush
            // messages only if our mailer has been created (potentially used)
            if ($app['mailer.initialized']) {
                $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);
            }
        });
    }
}
