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

        $app['mailer'] = $app->share(function () use ($app) {
            $r = new \ReflectionClass('Swift_Mailer');
            require_once dirname($r->getFilename()).'/../../swift_init.php';

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
        if (isset($app['swiftmailer.class_path'])) {
            require_once $app['swiftmailer.class_path'].'/Swift.php';

            \Swift::registerAutoload($app['swiftmailer.class_path'].'/../swift_init.php');
        }

        $app->finish(function () use ($app) {
            $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);
        });
    }
}
