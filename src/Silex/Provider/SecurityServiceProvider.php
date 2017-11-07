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
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\AccessListener;
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
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Guard\Firewall\GuardAuthenticationListener;
use Symfony\Component\Security\Guard\Provider\GuardAuthenticationProvider;

/**
 * Symfony Security component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
    protected $fakeRoutes;

    public function register(Container $app)
    {
        // used to register routes for login_check and logout
        $this->fakeRoutes = array();

        $that = $this;

        $app['security.role_hierarchy'] = array();
        $app['security.access_rules'] = array();
        $app['security.hide_user_not_found'] = true;
        $app['security.encoder.bcrypt.cost'] = 13;

        $app['security.authorization_checker'] = function ($app) {
            return new AuthorizationChecker($app['security.token_storage'], $app['security.authentication_manager'], $app['security.access_manager']);
        };

        $app['security.token_storage'] = function ($app) {
            return new TokenStorage();
        };

        $app['user'] = $app->factory(function ($app) {
            if (null === $token = $app['security.token_storage']->getToken()) {
                return;
            }

            if (!is_object($user = $token->getUser())) {
                return;
            }

            return $user;
        });

        $app['security.authentication_manager'] = function ($app) {
            $manager = new AuthenticationProviderManager($app['security.authentication_providers']);
            $manager->setEventDispatcher($app['dispatcher']);

            return $manager;
        };

        // by default, all users use the digest encoder
        $app['security.encoder_factory'] = function ($app) {
            return new EncoderFactory(array(
                'Symfony\Component\Security\Core\User\UserInterface' => $app['security.default_encoder'],
            ));
        };

        // by default, all users use the BCrypt encoder
        $app['security.default_encoder'] = function ($app) {
            return $app['security.encoder.bcrypt'];
        };

        $app['security.encoder.digest'] = function ($app) {
            return new MessageDigestPasswordEncoder();
        };

        $app['security.encoder.bcrypt'] = function ($app) {
            return new BCryptPasswordEncoder($app['security.encoder.bcrypt.cost']);
        };

        $app['security.encoder.pbkdf2'] = function ($app) {
            return new Pbkdf2PasswordEncoder();
        };

        $app['security.user_checker'] = function ($app) {
            return new UserChecker();
        };

        $app['security.access_manager'] = function ($app) {
            return new AccessDecisionManager($app['security.voters']);
        };

        $app['security.voters'] = function ($app) {
            return array(
                new RoleHierarchyVoter(new RoleHierarchy($app['security.role_hierarchy'])),
                new AuthenticatedVoter($app['security.trust_resolver']),
            );
        };

        $app['security.firewall'] = function ($app) {
            return new Firewall($app['security.firewall_map'], $app['dispatcher']);
        };

        $app['security.channel_listener'] = function ($app) {
            return new ChannelListener(
                $app['security.access_map'],
                new RetryAuthenticationEntryPoint(
                    isset($app['request.http_port']) ? $app['request.http_port'] : 80,
                    isset($app['request.https_port']) ? $app['request.https_port'] : 443
                ),
                $app['logger']
            );
        };

        // generate the build-in authentication factories
        foreach (array('logout', 'pre_auth', 'guard', 'form', 'http', 'remember_me', 'anonymous') as $type) {
            $entryPoint = null;
            if ('http' === $type) {
                $entryPoint = 'http';
            } elseif ('form' === $type) {
                $entryPoint = 'form';
            } elseif ('guard' === $type) {
                $entryPoint = 'guard';
            }

            $app['security.authentication_listener.factory.'.$type] = $app->protect(function ($name, $options) use ($type, $app, $entryPoint) {
                if ($entryPoint && !isset($app['security.entry_point.'.$name.'.'.$entryPoint])) {
                    $app['security.entry_point.'.$name.'.'.$entryPoint] = $app['security.entry_point.'.$entryPoint.'._proto']($name, $options);
                }

                if (!isset($app['security.authentication_listener.'.$name.'.'.$type])) {
                    $app['security.authentication_listener.'.$name.'.'.$type] = $app['security.authentication_listener.'.$type.'._proto']($name, $options);
                }

                $provider = 'dao';
                if ('anonymous' === $type) {
                    $provider = 'anonymous';
                } elseif ('guard' === $type) {
                    $provider = 'guard';
                }
                if (!isset($app['security.authentication_provider.'.$name.'.'.$provider])) {
                    $app['security.authentication_provider.'.$name.'.'.$provider] = $app['security.authentication_provider.'.$provider.'._proto']($name, $options);
                }

                return array(
                    'security.authentication_provider.'.$name.'.'.$provider,
                    'security.authentication_listener.'.$name.'.'.$type,
                    $entryPoint ? 'security.entry_point.'.$name.'.'.$entryPoint : null,
                    $type,
                );
            });
        }

        $app['security.firewall_map'] = function ($app) {
            $positions = array('logout', 'pre_auth', 'guard', 'form', 'http', 'remember_me', 'anonymous');
            $providers = array();
            $configs = array();
            foreach ($app['security.firewalls'] as $name => $firewall) {
                $entryPoint = null;
                $pattern = isset($firewall['pattern']) ? $firewall['pattern'] : null;
                $users = isset($firewall['users']) ? $firewall['users'] : array();
                $security = isset($firewall['security']) ? (bool) $firewall['security'] : true;
                $stateless = isset($firewall['stateless']) ? (bool) $firewall['stateless'] : false;
                $context = isset($firewall['context']) ? $firewall['context'] : $name;
                $hosts = isset($firewall['hosts']) ? $firewall['hosts'] : null;
                $methods = isset($firewall['methods']) ? $firewall['methods'] : null;
                unset($firewall['pattern'], $firewall['users'], $firewall['security'], $firewall['stateless'], $firewall['context'], $firewall['methods'], $firewall['hosts']);
                $protected = false === $security ? false : count($firewall);
                $listeners = array('security.channel_listener');

                if ($protected) {
                    if (!isset($app['security.context_listener.'.$name])) {
                        if (!isset($app['security.user_provider.'.$name])) {
                            $app['security.user_provider.'.$name] = is_array($users) ? $app['security.user_provider.inmemory._proto']($users) : $users;
                        }

                        $app['security.context_listener.'.$name] = $app['security.context_listener._proto']($name, array($app['security.user_provider.'.$name]));
                    }

                    if (false === $stateless) {
                        $listeners[] = 'security.context_listener.'.$context;
                    }

                    $factories = array();
                    foreach ($positions as $position) {
                        $factories[$position] = array();
                    }

                    foreach ($firewall as $type => $options) {
                        if ('switch_user' === $type) {
                            continue;
                        }

                        // normalize options
                        if (!is_array($options)) {
                            if (!$options) {
                                continue;
                            }

                            $options = array();
                        }

                        if (!isset($app['security.authentication_listener.factory.'.$type])) {
                            throw new \LogicException(sprintf('The "%s" authentication entry is not registered.', $type));
                        }

                        $options['stateless'] = $stateless;

                        list($providerId, $listenerId, $entryPointId, $position) = $app['security.authentication_listener.factory.'.$type]($name, $options);

                        if (null !== $entryPointId) {
                            $entryPoint = $entryPointId;
                        }

                        $factories[$position][] = $listenerId;
                        $providers[] = $providerId;
                    }

                    foreach ($positions as $position) {
                        foreach ($factories[$position] as $listener) {
                            $listeners[] = $listener;
                        }
                    }

                    $listeners[] = 'security.access_listener';

                    if (isset($firewall['switch_user'])) {
                        $app['security.switch_user.'.$name] = $app['security.authentication_listener.switch_user._proto']($name, $firewall['switch_user']);

                        $listeners[] = 'security.switch_user.'.$name;
                    }

                    if (!isset($app['security.exception_listener.'.$name])) {
                        if (null === $entryPoint) {
                            $app[$entryPoint = 'security.entry_point.'.$name.'.form'] = $app['security.entry_point.form._proto']($name, array());
                        }
                        $accessDeniedHandler = null;
                        if (isset($app['security.access_denied_handler.'.$name])) {
                            $accessDeniedHandler = $app['security.access_denied_handler.'.$name];
                        }
                        $app['security.exception_listener.'.$name] = $app['security.exception_listener._proto']($entryPoint, $name, $accessDeniedHandler);
                    }
                }

                $configs[$name] = array(
                    'pattern' => $pattern,
                    'listeners' => $listeners,
                    'protected' => $protected,
                    'methods' => $methods,
                    'hosts' => $hosts,
                );
            }

            $app['security.authentication_providers'] = array_map(function ($provider) use ($app) {
                return $app[$provider];
            }, array_unique($providers));

            $map = new FirewallMap();
            foreach ($configs as $name => $config) {
                if (is_string($config['pattern'])) {
                    $requestMatcher = new RequestMatcher($config['pattern'], $config['hosts'], $config['methods']);
                } else {
                    $requestMatcher = $config['pattern'];
                }

                $map->add(
                    $requestMatcher,
                    array_map(function ($listenerId) use ($app, $name) {
                        $listener = $app[$listenerId];

                        if (isset($app['security.remember_me.service.'.$name])) {
                            if ($listener instanceof AbstractAuthenticationListener || $listener instanceof GuardAuthenticationListener) {
                                $listener->setRememberMeServices($app['security.remember_me.service.'.$name]);
                            }
                            if ($listener instanceof LogoutListener) {
                                $listener->addHandler($app['security.remember_me.service.'.$name]);
                            }
                        }

                        return $listener;
                    }, $config['listeners']),
                    $config['protected'] ? $app['security.exception_listener.'.$name] : null
                );
            }

            return $map;
        };

        $app['security.access_listener'] = function ($app) {
            return new AccessListener(
                $app['security.token_storage'],
                $app['security.access_manager'],
                $app['security.access_map'],
                $app['security.authentication_manager'],
                $app['logger']
            );
        };

        $app['security.access_map'] = function ($app) {
            $map = new AccessMap();

            foreach ($app['security.access_rules'] as $rule) {
                if (is_string($rule[0])) {
                    $rule[0] = new RequestMatcher($rule[0]);
                } elseif (is_array($rule[0])) {
                    $rule[0] += array(
                        'path' => null,
                        'host' => null,
                        'methods' => null,
                        'ips' => null,
                        'attributes' => array(),
                        'schemes' => null,
                    );
                    $rule[0] = new RequestMatcher($rule[0]['path'], $rule[0]['host'], $rule[0]['methods'], $rule[0]['ips'], $rule[0]['attributes'], $rule[0]['schemes']);
                }
                $map->add($rule[0], (array) $rule[1], isset($rule[2]) ? $rule[2] : null);
            }

            return $map;
        };

        $app['security.trust_resolver'] = function ($app) {
            return new AuthenticationTrustResolver('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken', 'Symfony\Component\Security\Core\Authentication\Token\RememberMeToken');
        };

        $app['security.session_strategy'] = function ($app) {
            return new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE);
        };

        $app['security.http_utils'] = function ($app) {
            return new HttpUtils($app['url_generator'], $app['request_matcher']);
        };

        $app['security.last_error'] = $app->protect(function (Request $request) {
            if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
                return $request->attributes->get(Security::AUTHENTICATION_ERROR)->getMessage();
            }

            $session = $request->getSession();
            if ($session && $session->has(Security::AUTHENTICATION_ERROR)) {
                $message = $session->get(Security::AUTHENTICATION_ERROR)->getMessage();
                $session->remove(Security::AUTHENTICATION_ERROR);

                return $message;
            }
        });

        // prototypes (used by the Firewall Map)

        $app['security.context_listener._proto'] = $app->protect(function ($providerKey, $userProviders) use ($app) {
            return function () use ($app, $userProviders, $providerKey) {
                return new ContextListener(
                    $app['security.token_storage'],
                    $userProviders,
                    $providerKey,
                    $app['logger'],
                    $app['dispatcher']
                );
            };
        });

        $app['security.user_provider.inmemory._proto'] = $app->protect(function ($params) use ($app) {
            return function () use ($app, $params) {
                $users = array();
                foreach ($params as $name => $user) {
                    $users[$name] = array('roles' => (array) $user[0], 'password' => $user[1]);
                }

                return new InMemoryUserProvider($users);
            };
        });

        $app['security.exception_listener._proto'] = $app->protect(function ($entryPoint, $name, $accessDeniedHandler = null) use ($app) {
            return function () use ($app, $entryPoint, $name, $accessDeniedHandler) {
                return new ExceptionListener(
                    $app['security.token_storage'],
                    $app['security.trust_resolver'],
                    $app['security.http_utils'],
                    $name,
                    $app[$entryPoint],
                    null, // errorPage
                    $accessDeniedHandler,
                    $app['logger']
                );
            };
        });

        $app['security.authentication.success_handler._proto'] = $app->protect(function ($name, $options) use ($app) {
            return function () use ($name, $options, $app) {
                $handler = new DefaultAuthenticationSuccessHandler(
                    $app['security.http_utils'],
                    $options
                );
                $handler->setProviderKey($name);

                return $handler;
            };
        });

        $app['security.authentication.failure_handler._proto'] = $app->protect(function ($name, $options) use ($app) {
            return function () use ($name, $options, $app) {
                return new DefaultAuthenticationFailureHandler(
                    $app,
                    $app['security.http_utils'],
                    $options,
                    $app['logger']
                );
            };
        });

        $app['security.authentication_listener.guard._proto'] = $app->protect(function ($providerKey, $options) use ($app, $that) {
            return function () use ($app, $providerKey, $options, $that) {
                if (!isset($app['security.authentication.guard_handler'])) {
                    $app['security.authentication.guard_handler'] = new GuardAuthenticatorHandler($app['security.token_storage'], $app['dispatcher']);
                }

                $authenticators = array();
                foreach ($options['authenticators'] as $authenticatorId) {
                    $authenticators[] = $app[$authenticatorId];
                }

                return new GuardAuthenticationListener(
                    $app['security.authentication.guard_handler'],
                    $app['security.authentication_manager'],
                    $providerKey,
                    $authenticators,
                    $app['logger']
                );
            };
        });

        $app['security.authentication_listener.form._proto'] = $app->protect(function ($name, $options) use ($app, $that) {
            return function () use ($app, $name, $options, $that) {
                $that->addFakeRoute(
                    'match',
                    $tmp = isset($options['check_path']) ? $options['check_path'] : '/login_check',
                    str_replace('/', '_', ltrim($tmp, '/'))
                );

                $class = isset($options['listener_class']) ? $options['listener_class'] : 'Symfony\\Component\\Security\\Http\\Firewall\\UsernamePasswordFormAuthenticationListener';

                if (!isset($app['security.authentication.success_handler.'.$name])) {
                    $app['security.authentication.success_handler.'.$name] = $app['security.authentication.success_handler._proto']($name, $options);
                }

                if (!isset($app['security.authentication.failure_handler.'.$name])) {
                    $app['security.authentication.failure_handler.'.$name] = $app['security.authentication.failure_handler._proto']($name, $options);
                }

                return new $class(
                    $app['security.token_storage'],
                    $app['security.authentication_manager'],
                    isset($app['security.session_strategy.'.$name]) ? $app['security.session_strategy.'.$name] : $app['security.session_strategy'],
                    $app['security.http_utils'],
                    $name,
                    $app['security.authentication.success_handler.'.$name],
                    $app['security.authentication.failure_handler.'.$name],
                    $options,
                    $app['logger'],
                    $app['dispatcher'],
                    isset($options['with_csrf']) && $options['with_csrf'] && isset($app['csrf.token_manager']) ? $app['csrf.token_manager'] : null
                );
            };
        });

        $app['security.authentication_listener.http._proto'] = $app->protect(function ($providerKey, $options) use ($app) {
            return function () use ($app, $providerKey, $options) {
                return new BasicAuthenticationListener(
                    $app['security.token_storage'],
                    $app['security.authentication_manager'],
                    $providerKey,
                    $app['security.entry_point.'.$providerKey.'.http'],
                    $app['logger']
                );
            };
        });

        $app['security.authentication_listener.anonymous._proto'] = $app->protect(function ($providerKey, $options) use ($app) {
            return function () use ($app, $providerKey, $options) {
                return new AnonymousAuthenticationListener(
                    $app['security.token_storage'],
                    $providerKey,
                    $app['logger']
                );
            };
        });

        $app['security.authentication.logout_handler._proto'] = $app->protect(function ($name, $options) use ($app) {
            return function () use ($name, $options, $app) {
                return new DefaultLogoutSuccessHandler(
                    $app['security.http_utils'],
                    isset($options['target_url']) ? $options['target_url'] : '/'
                );
            };
        });

        $app['security.authentication_listener.logout._proto'] = $app->protect(function ($name, $options) use ($app, $that) {
            return function () use ($app, $name, $options, $that) {
                $that->addFakeRoute(
                    'get',
                    $tmp = isset($options['logout_path']) ? $options['logout_path'] : '/logout',
                    str_replace('/', '_', ltrim($tmp, '/'))
                );

                if (!isset($app['security.authentication.logout_handler.'.$name])) {
                    $app['security.authentication.logout_handler.'.$name] = $app['security.authentication.logout_handler._proto']($name, $options);
                }

                $listener = new LogoutListener(
                    $app['security.token_storage'],
                    $app['security.http_utils'],
                    $app['security.authentication.logout_handler.'.$name],
                    $options,
                    isset($options['with_csrf']) && $options['with_csrf'] && isset($app['csrf.token_manager']) ? $app['csrf.token_manager'] : null
                );

                $invalidateSession = isset($options['invalidate_session']) ? $options['invalidate_session'] : true;
                if (true === $invalidateSession && false === $options['stateless']) {
                    $listener->addHandler(new SessionLogoutHandler());
                }

                return $listener;
            };
        });

        $app['security.authentication_listener.switch_user._proto'] = $app->protect(function ($name, $options) use ($app, $that) {
            return function () use ($app, $name, $options, $that) {
                return new SwitchUserListener(
                    $app['security.token_storage'],
                    $app['security.user_provider.'.$name],
                    $app['security.user_checker'],
                    $name,
                    $app['security.access_manager'],
                    $app['logger'],
                    isset($options['parameter']) ? $options['parameter'] : '_switch_user',
                    isset($options['role']) ? $options['role'] : 'ROLE_ALLOWED_TO_SWITCH',
                    $app['dispatcher']
                );
            };
        });

        $app['security.entry_point.form._proto'] = $app->protect(function ($name, array $options) use ($app) {
            return function () use ($app, $options) {
                $loginPath = isset($options['login_path']) ? $options['login_path'] : '/login';
                $useForward = isset($options['use_forward']) ? $options['use_forward'] : false;

                return new FormAuthenticationEntryPoint($app, $app['security.http_utils'], $loginPath, $useForward);
            };
        });

        $app['security.entry_point.http._proto'] = $app->protect(function ($name, array $options) use ($app) {
            return function () use ($app, $name, $options) {
                return new BasicAuthenticationEntryPoint(isset($options['real_name']) ? $options['real_name'] : 'Secured');
            };
        });

        $app['security.entry_point.guard._proto'] = $app->protect(function ($name, array $options) use ($app) {
            if (isset($options['entry_point'])) {
                // if it's configured explicitly, use it!
                return $app[$options['entry_point']];
            }
            $authenticatorIds = $options['authenticators'];
            if (count($authenticatorIds) == 1) {
                // if there is only one authenticator, use that as the entry point
                return $app[reset($authenticatorIds)];
            }
            // we have multiple entry points - we must ask them to configure one
            throw new \LogicException(sprintf(
                'Because you have multiple guard configurators, you need to set the "guard.entry_point" key to one of your configurators (%s)',
                implode(', ', $authenticatorIds)
            ));
        });

        $app['security.authentication_provider.dao._proto'] = $app->protect(function ($name, $options) use ($app) {
            return function () use ($app, $name) {
                return new DaoAuthenticationProvider(
                    $app['security.user_provider.'.$name],
                    $app['security.user_checker'],
                    $name,
                    $app['security.encoder_factory'],
                    $app['security.hide_user_not_found']
                );
            };
        });

        $app['security.authentication_provider.guard._proto'] = $app->protect(function ($name, $options) use ($app) {
            return function () use ($app, $name, $options) {
                $authenticators = array();
                foreach ($options['authenticators'] as $authenticatorId) {
                    $authenticators[] = $app[$authenticatorId];
                }

                return new GuardAuthenticationProvider(
                    $authenticators,
                    $app['security.user_provider.'.$name],
                    $name,
                    $app['security.user_checker']
                );
            };
        });

        $app['security.authentication_provider.anonymous._proto'] = $app->protect(function ($name, $options) use ($app) {
            return function () use ($app, $name) {
                return new AnonymousAuthenticationProvider($name);
            };
        });

        if (isset($app['validator'])) {
            $app['security.validator.user_password_validator'] = function ($app) {
                return new UserPasswordValidator($app['security.token_storage'], $app['security.encoder_factory']);
            };

            $app['validator.validator_service_ids'] = array_merge($app['validator.validator_service_ids'], array('security.validator.user_password' => 'security.validator.user_password_validator'));
        }
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['security.firewall']);
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        foreach ($this->fakeRoutes as $route) {
            list($method, $pattern, $name) = $route;

            $controllers->$method($pattern)->run(null)->bind($name);
        }

        return $controllers;
    }

    public function boot(Application $app)
    {
        $app->mount('/', $this->connect($app));
    }

    public function addFakeRoute($method, $pattern, $name)
    {
        $this->fakeRoutes[] = array($method, $pattern, $name);
    }
}
