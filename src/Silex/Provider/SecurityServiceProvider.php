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

use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\ChannelListener;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;
use Symfony\Component\Security\Http\EntryPoint\BasicAuthenticationEntryPoint;
use Symfony\Component\Security\Http\EntryPoint\RetryAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;
use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Symfony Security component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityServiceProvider implements ServiceProviderInterface
{
    protected $fakeRoutes;

    public function register(Application $app)
    {
        // used to register routes for login_check and logout
        $this->fakeRoutes = array();

        $that = $this;

        $app['security.role_hierarchy'] = array();
        $app['security.access_rules'] = array();

        $app['security'] = $app->share(function () use ($app) {
            return new SecurityContext($app['security.authentication_manager'], $app['security.access_manager']);
        });

        $app['security.authentication_manager'] = $app->share(function () use ($app) {
            $manager = new AuthenticationProviderManager($app['security.authentication_providers']);
            $manager->setEventDispatcher($app['dispatcher']);

            return $manager;
        });

        // by default, all users use the digest encoder
        $app['security.encoder_factory'] = $app->share(function () use ($app) {
            return new EncoderFactory(array(
                'Symfony\Component\Security\Core\User\UserInterface' => $app['security.encoder.digest'],
            ));
        });

        $app['security.encoder.digest'] = $app->share(function () use ($app) {
            return new MessageDigestPasswordEncoder();
        });

        $app['security.user_checker'] = $app->share(function () use ($app) {
            return new UserChecker();
        });

        $app['security.access_manager'] = $app->share(function () use ($app) {
            return new AccessDecisionManager($app['security.voters']);
        });

        $app['security.voters'] = $app->share(function () use ($app) {
            return array(
                new RoleHierarchyVoter(new RoleHierarchy($app['security.role_hierarchy'])),
                new AuthenticatedVoter($app['security.trust_resolver']),
            );
        });

        $app['security.firewall'] = $app->share(function () use ($app) {
            return new Firewall($app['security.firewall_map'], $app['dispatcher']);
        });

        $app['security.channel_listener'] = $app->share(function () use ($app) {
            return new ChannelListener(
                $app['security.access_map'],
                new RetryAuthenticationEntryPoint($app['request.http_port'], $app['request.https_port']),
                $app['logger']
            );
        });

        $app['security.firewall_map'] = $app->share(function () use ($app) {
            $map = new FirewallMap();
            $entryPoint = 'form';
            foreach ($app['security.firewalls'] as $name => $firewall) {
                $pattern = isset($firewall['pattern']) ? $firewall['pattern'] : null;
                $users = isset($firewall['users']) ? $firewall['users'] : array();
                unset($firewall['pattern'], $firewall['users']);

                $protected = count($firewall);

                $listeners = array($app['security.channel_listener']);

                if ($protected) {
                    if (!isset($app['security.context_listener.'.$name])) {
                        if (!isset($app['security.user_provider.'.$name])) {
                            $app['security.user_provider.'.$name] = is_array($users) ? $app['security.user_provider.inmemory._proto']($users) : $users;
                        }

                        $app['security.context_listener.'.$name] = $app['security.context_listener._proto'](
                            $name,
                            array($app['security.user_provider.'.$name])
                        );
                    }

                    $listeners[] = $app['security.context_listener.'.$name];
                }

                if (count($firewall)) {
                    foreach (array('logout', 'pre_auth', 'form', 'http', 'remember_me', 'anonymous') as $type) {
                        if (isset($firewall[$type])) {
                            $options = $firewall[$type];

                            // normalize options
                            if (!is_array($options)) {
                                if (!$options) {
                                    continue;
                                }

                                $options = array();
                            }

                            if ('http' == $type) {
                                $entryPoint = 'http';
                            }

                            if (!isset($app['security.authentication.'.$name.'.'.$type])) {
                                if (!isset($app['security.entry_point.'.$entryPoint.'.'.$name])) {
                                    $app['security.entry_point.'.$entryPoint.'.'.$name] = $app['security.entry_point.'.$entryPoint.'._proto']($name);
                                }

                                $app['security.authentication.'.$name.'.'.$type] = $app['security.authentication.'.$type.'._proto']($name, $options);
                            }

                            $listeners[] = $app['security.authentication.'.$name.'.'.$type];
                        }
                    }

                    if ($protected) {
                        $listeners[] = $app['security.access_listener'];

                        if (isset($firewall['switch_user'])) {
                            $listeners[] = $app['security.authentication.switch_user._proto']($name, $firewall['switch_user']);
                        }
                    }
                }

                if ($protected && !isset($app['security.exception_listener.'.$name])) {
                    $app['security.exception_listener.'.$name] = $app['security.exception_listener._proto']($entryPoint, $name);
                }

                $map->add(
                    is_string($pattern) ? new RequestMatcher($pattern) : $pattern,
                    $listeners,
                    $protected ? $app['security.exception_listener.'.$name] : null
                );
            }

            return $map;
        });

        $app['security.authentication_providers'] = $app->share(function () use ($app) {
            $providers = array();
            foreach ($app['security.firewalls'] as $name => $firewall) {
                unset($firewall['pattern'], $firewall['users']);

                if (!count($firewall)) {
                    continue;
                }

                if (!isset($app['security.authentication_provider.'.$name])) {
                    $a = 'anonymous' == $name ? 'anonymous' : 'dao';
                    $app['security.authentication_provider.'.$name] = $app['security.authentication_provider.'.$a.'._proto']($name);
                }
                $providers[] = $app['security.authentication_provider.'.$name];
            }

            return $providers;
        });

        $app['security.access_listener'] = $app->share(function () use ($app) {
            return new AccessListener(
                $app['security'],
                $app['security.access_manager'],
                $app['security.access_map'],
                $app['security.authentication_manager'],
                $app['logger']
            );
        });

        $app['security.access_map'] = $app->share(function () use ($app) {
            $map = new AccessMap();

            foreach ($app['security.access_rules'] as $rule) {
                if (is_string($rule[0])) {
                    $rule[0] = new RequestMatcher($rule[0]);
                }

                $map->add($rule[0], (array) $rule[1], isset($rule[2]) ? $rule[2] : null);
            }

            return $map;
        });

        $app['security.trust_resolver'] = $app->share(function () use ($app) {
            return new AuthenticationTrustResolver('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken', 'Symfony\Component\Security\Core\Authentication\Token\RememberMeToken');
        });

        $app['security.session_strategy'] = $app->share(function () use ($app) {
            return new SessionAuthenticationStrategy('migrate');
        });

        $app['security.http_utils'] = $app->share(function () use ($app) {
            return new HttpUtils();
        });

        $app['security.last_error'] = $app->protect(function (Request $request) {
            if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
                return $request->attributes->get(SecurityContextInterface::AUTHENTICATION_ERROR)->getMessage();
            }

            $session = $request->getSession();
            if ($session && $session->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
                $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR)->getMessage();
                $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);

                return $error;
            }
        });

        // prototypes (used by the Firewall Map)

        $app['security.context_listener._proto'] = $app->protect(function ($providerKey, $userProviders) use ($app) {
            return new ContextListener(
                $app['security'],
                $userProviders,
                $providerKey,
                $app['logger'],
                $app['dispatcher']
            );
        });

        $app['security.user_provider.inmemory._proto'] = $app->protect(function ($params) use ($app) {
            $users = array();
            foreach ($params as $name => $user) {
                $users[$name] = array('roles' => (array) $user[0], 'password' => $user[1]);
            }

            return new InMemoryUserProvider($users);
        });

        $app['security.exception_listener._proto'] = $app->protect(function ($entryPoint, $name) use ($app) {
            if (!isset($app['security.entry_point.'.$entryPoint.'.'.$name])) {
                $app['security.entry_point.'.$entryPoint.'.'.$name] = $app['security.entry_point.'.$entryPoint.'._proto']($name);
            }

            return new ExceptionListener(
                $app['security'],
                $app['security.trust_resolver'],
                $app['security.http_utils'],
                $app['security.entry_point.'.$entryPoint.'.'.$name],
                null, // errorPage
                null, // AccessDeniedHandlerInterface
                $app['logger']
            );
        });

        $app['security.authentication.form._proto'] = $app->protect(function ($providerKey, $options) use ($app, $that) {
            $that->addFakeRoute(array('post', $tmp = isset($options['check_path']) ? $options['check_path'] : '/login_check', str_replace('/', '_', ltrim($tmp, '/'))));

            return new UsernamePasswordFormAuthenticationListener(
                $app['security'],
                $app['security.authentication_manager'],
                $app['security.session_strategy'],
                $app['security.http_utils'],
                $providerKey,
                $options,
                null, // AuthenticationSuccessHandlerInterface
                null, // AuthenticationFailureHandlerInterface
                $app['logger'],
                $app['dispatcher'],
                isset($options['with_csrf']) && $options['with_csrf'] && isset($app['form.csrf_provider']) ? $app['form.csrf_provider'] : null
            );
        });

        $app['security.authentication.http._proto'] = $app->protect(function ($providerKey, $options) use ($app) {
            return new BasicAuthenticationListener(
                $app['security'],
                $app['security.authentication_manager'],
                $providerKey,
                $app['security.entry_point.http.'.$providerKey],
                $app['logger']
            );
        });

        $app['security.authentication.anonymous._proto'] = $app->protect(function ($providerKey, $options) use ($app) {
            return new AnonymousAuthenticationListener(
                $app['security'],
                $providerKey,
                $app['logger']
            );
        });

        $app['security.authentication.logout._proto'] = $app->protect(function ($providerKey, $options) use ($app, $that) {
            $that->addFakeRoute(array('get', $tmp = isset($options['logout_path']) ? $options['logout_path'] : '/logout', str_replace('/', '_', ltrim($tmp, '/'))));

            $listener = new LogoutListener(
                $app['security'],
                $app['security.http_utils'],
                $options,
                null, // LogoutSuccessHandlerInterface
                isset($options['with_csrf']) && $options['with_csrf'] && isset($app['form.csrf_provider']) ? $app['form.csrf_provider'] : null
            );

            $listener->addHandler(new SessionLogoutHandler());

            return $listener;
        });

        $app['security.authentication.switch_user._proto'] = $app->protect(function ($name, $options) use ($app, $that) {
            return new SwitchUserListener(
                $app['security'],
                $app['security.user_provider.'.$name],
                $app['security.user_checker'],
                $name,
                $app['security.access_manager'],
                $app['logger'],
                isset($options['parameter']) ? $options['parameter'] : '_switch_user',
                isset($options['role']) ? $options['role'] : 'ROLE_ALLOWED_TO_SWITCH',
                $app['dispatcher']
            );
        });

        $app['security.entry_point.form._proto'] = $app->protect(function ($name, $loginPath = '/login', $useForward = false) use ($app) {
            return new FormAuthenticationEntryPoint($app, $app['security.http_utils'], $loginPath, $useForward);
        });

        $app['security.entry_point.http._proto'] = $app->protect(function ($name, $realName = 'Secured') use ($app) {
            return new BasicAuthenticationEntryPoint($realName);
        });

        $app['security.authentication_provider.dao._proto'] = $app->protect(function ($name) use ($app) {
            return new DaoAuthenticationProvider(
                $app['security.user_provider.'.$name],
                $app['security.user_checker'],
                $name,
                $app['security.encoder_factory']
            );
        });

        $app['security.authentication_provider.anonymous._proto'] = $app->protect(function ($name) use ($app) {
            return new AnonymousAuthenticationProvider($name);
        });
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addListener('kernel.request', array($app['security.firewall'], 'onKernelRequest'), 8);

        foreach ($this->fakeRoutes as $route) {
            $method = $route[0];

            $app->$method($route[1], function() {})->bind($route[2]);
        }
    }

    public function addFakeRoute($route)
    {
        $this->fakeRoutes[] = $route;
    }
}
