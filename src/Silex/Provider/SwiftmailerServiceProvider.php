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

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Swiftmailer Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwiftmailerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['swiftmailer.options'] = array();

        $app['mailer.initialized'] = false;

        $app['mailer'] = $app->share(function () use ($app) {
            $app['mailer.initialized'] = true;

            return new \Swift_Mailer($app['swiftmailer.spooltransport']);
        });

        $app['swiftmailer.spooltransport'] = $app->share(function () use ($app) {
            return new \Swift_SpoolTransport($app['swiftmailer.spool']);
        });

        $app['swiftmailer.spool'] = $app->share(function () use ($app) {
            return new \Swift_MemorySpool();
        });

        $app['swiftmailer.transport'] = $app->share(function () use ($app) {
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
        });

        $app['swiftmailer.transport.buffer'] = $app->share(function () {
            return new \Swift_Transport_StreamBuffer(new \Swift_StreamFilters_StringReplacementFilterFactory());
        });

        $app['swiftmailer.transport.authhandler'] = $app->share(function () {
            return new \Swift_Transport_Esmtp_AuthHandler(array(
                new \Swift_Transport_Esmtp_Auth_CramMd5Authenticator(),
                new \Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                new \Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
            ));
        });

        $app['swiftmailer.transport.eventdispatcher'] = $app->share(function () {
            return new \Swift_Events_SimpleEventDispatcher();
        });
    }

    public function boot(Application $app)
    {
        // BC: to be removed before 1.0
        if (isset($app['swiftmailer.class_path'])) {
            throw new \RuntimeException('You have provided the swiftmailer.class_path parameter. The autoloader has been removed from Silex. It is recommended that you use Composer to manage your dependencies and handle your autoloading. If you are already using Composer, you can remove the parameter. See http://getcomposer.org for more information.');
        }

        $app->finish(function () use ($app) {
            // To speed things up (by avoiding Swift Mailer initialization), flush
            // messages only if our mailer has been created (potentially used)
            if ($app['mailer.initialized']) {
                $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);
            }
        });
    }
}
