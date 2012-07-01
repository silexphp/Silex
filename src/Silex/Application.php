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

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Silex\RedirectableUrlMatcher;
use Silex\ControllerResolver;

/**
 * The Silex framework class.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Application extends \Pimple implements HttpKernelInterface, EventSubscriberInterface, TerminableInterface
{
    const VERSION = '1.0-DEV';

    private $providers = array();
    private $booted = false;
    private $beforeDispatched = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $app = $this;

        $this['logger'] = null;

        $this['autoloader'] = function () {
            throw new \RuntimeException('You tried to access the autoloader service. The autoloader has been removed from Silex. It is recommended that you use Composer to manage your dependencies and handle your autoloading. See http://getcomposer.org for more information.');
        };

        $this['routes'] = $this->share(function () {
            return new RouteCollection();
        });

        $this['controllers'] = $this->share(function () use ($app) {
            return $app['controllers_factory'];
        });

        $this['controllers_factory'] = function () use ($app) {
            return new ControllerCollection($app['route_factory']);
        };

        $this['route_class'] = 'Silex\\Route';
        $this['route_factory'] = function () use ($app) {
            return new $app['route_class']();
        };

        $this['exception_handler'] = $this->share(function () {
            return new ExceptionHandler();
        });

        $this['dispatcher'] = $this->share(function () use ($app) {
            $dispatcher = new EventDispatcher();
            $dispatcher->addSubscriber($app);

            $urlMatcher = new LazyUrlMatcher(function () use ($app) {
                return $app['url_matcher'];
            });
            $dispatcher->addSubscriber(new RouterListener($urlMatcher, $app['request_context'], $app['logger']));
            $dispatcher->addSubscriber(new LocaleListener($app['locale'], $urlMatcher));

            return $dispatcher;
        });

        $this['resolver'] = $this->share(function () use ($app) {
            return new ControllerResolver($app, $app['logger']);
        });

        $this['kernel'] = $this->share(function () use ($app) {
            return new HttpKernel($app['dispatcher'], $app['resolver']);
        });

        $this['request_context'] = $this->share(function () use ($app) {
            $context = new RequestContext();

            $context->setHttpPort($app['request.http_port']);
            $context->setHttpsPort($app['request.https_port']);

            return $context;
        });

        $this['url_matcher'] = $this->share(function () use ($app) {
            return new RedirectableUrlMatcher($app['routes'], $app['request_context']);
        });

        $this['route_before_middlewares_trigger'] = $this->protect(function (GetResponseEvent $event) use ($app) {
            $request = $event->getRequest();
            $routeName = $request->attributes->get('_route');
            if (!$route = $app['routes']->get($routeName)) {
                return;
            }

            foreach ((array) $route->getOption('_before_middlewares') as $callback) {
                $ret = call_user_func($callback, $request, $app);
                if ($ret instanceof Response) {
                    $event->setResponse($ret);

                    return;
                } elseif (null !== $ret) {
                    throw new \RuntimeException(sprintf('A before middleware for route "%s" returned an invalid response value. Must return null or an instance of Response.', $routeName));
                }
            }
        });

        $this['route_after_middlewares_trigger'] = $this->protect(function (FilterResponseEvent $event) use ($app) {
            $request = $event->getRequest();
            $routeName = $request->attributes->get('_route');
            if (!$route = $app['routes']->get($routeName)) {
                return;
            }

            foreach ((array) $route->getOption('_after_middlewares') as $callback) {
                $response = call_user_func($callback, $request, $event->getResponse());
                if ($response instanceof Response) {
                    $event->setResponse($response);
                } elseif (null !== $response) {
                    throw new \RuntimeException(sprintf('An after middleware for route "%s" returned an invalid response value. Must return null or an instance of Response.', $routeName));
                }
            }
        });

        $this['request_error'] = $this->protect(function () {
            throw new \RuntimeException('Accessed request service outside of request scope. Try moving that call to a before handler or controller.');
        });

        $this['request'] = $this['request_error'];

        $this['request.http_port'] = 80;
        $this['request.https_port'] = 443;
        $this['debug'] = false;
        $this['charset'] = 'UTF-8';
        $this['locale'] = 'en';
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array                    $values   An array of values that customizes the provider
     */
    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        $this->providers[] = $provider;

        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Boots all service providers.
     *
     * This method is automatically called by handle(), but you can use it
     * to boot all service providers when not handling a request.
     */
    public function boot()
    {
        if (!$this->booted) {
            foreach ($this->providers as $provider) {
                $provider->boot($this);
            }

            $this->booted = true;
        }
    }

    /**
     * Maps a pattern to a callable.
     *
     * You can optionally specify HTTP methods that should be matched.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function match($pattern, $to)
    {
        return $this['controllers']->match($pattern, $to);
    }

    /**
     * Maps a GET request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function get($pattern, $to)
    {
        return $this['controllers']->get($pattern, $to);
    }

    /**
     * Maps a POST request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function post($pattern, $to)
    {
        return $this['controllers']->post($pattern, $to);
    }

    /**
     * Maps a PUT request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function put($pattern, $to)
    {
        return $this['controllers']->put($pattern, $to);
    }

    /**
     * Maps a DELETE request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     *
     * @return Controller
     */
    public function delete($pattern, $to)
    {
        return $this['controllers']->delete($pattern, $to);
    }

    /**
     * Registers a before filter.
     *
     * Before filters are run before any route has been matched.
     *
     * @param mixed   $callback Before filter callback
     * @param integer $priority The higher this value, the earlier an event
     *                          listener will be triggered in the chain (defaults to 0)
     */
    public function before($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(SilexEvents::BEFORE, function (GetResponseEvent $event) use ($callback) {
            $ret = call_user_func($callback, $event->getRequest());

            if ($ret instanceof Response) {
                $event->setResponse($ret);
            }
        }, $priority);
    }

    /**
     * Registers an after filter.
     *
     * After filters are run after the controller has been executed.
     *
     * @param mixed   $callback After filter callback
     * @param integer $priority The higher this value, the earlier an event
     *                          listener will be triggered in the chain (defaults to 0)
     */
    public function after($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(SilexEvents::AFTER, function (FilterResponseEvent $event) use ($callback) {
            call_user_func($callback, $event->getRequest(), $event->getResponse());
        }, $priority);
    }

    /**
     * Registers a finish filter.
     *
     * Finish filters are run after the response has been sent.
     *
     * @param mixed   $callback Finish filter callback
     * @param integer $priority The higher this value, the earlier an event
     *                          listener will be triggered in the chain (defaults to 0)
     */
    public function finish($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(SilexEvents::FINISH, function (PostResponseEvent $event) use ($callback) {
            call_user_func($callback, $event->getRequest(), $event->getResponse());
        }, $priority);
    }

    /**
     * Aborts the current request by sending a proper HTTP error.
     *
     * @param integer $statusCode The HTTP status code
     * @param string  $message    The status message
     * @param array   $headers    An array of HTTP headers
     */
    public function abort($statusCode, $message = '', array $headers = array())
    {
        throw new HttpException($statusCode, $message, null, $headers);
    }

    /**
     * Registers an error handler.
     *
     * Error handlers are simple callables which take a single Exception
     * as an argument. If a controller throws an exception, an error handler
     * can return a specific response.
     *
     * When an exception occurs, all handlers will be called, until one returns
     * something (a string or a Response object), at which point that will be
     * returned to the client.
     *
     * For this reason you should add logging handlers before output handlers.
     *
     * @param mixed   $callback Error handler callback, takes an Exception argument
     * @param integer $priority The higher this value, the earlier an event
     *                          listener will be triggered in the chain (defaults to 0)
     */
    public function error($callback, $priority = 0)
    {
        $this['dispatcher']->addListener(SilexEvents::ERROR, function (GetResponseForExceptionEvent $event) use ($callback) {
            $exception = $event->getException();

            if (is_array($callback)) {
                $callbackReflection = new \ReflectionMethod($callback[0], $callback[1]);
            } elseif (is_object($callback) && !$callback instanceof \Closure) {
                $callbackReflection = new \ReflectionObject($callback);
                $callbackReflection = $callbackReflection->getMethod('__invoke');
            } else {
                $callbackReflection = new \ReflectionFunction($callback);
            }

            if ($callbackReflection->getNumberOfParameters() > 0) {
                $parameters = $callbackReflection->getParameters();
                $expectedException = $parameters[0];
                if ($expectedException->getClass() && !$expectedException->getClass()->isInstance($exception)) {
                    return;
                }
            }

            $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

            $result = call_user_func($callback, $exception, $code);

            if (null !== $result) {
                $response = $result instanceof Response ? $result : new Response((string) $result);

                $event->setResponse($response);
            }
        }, $priority);
    }

    /**
     * Flushes the controller collection.
     *
     * @param string $prefix The route prefix
     */
    public function flush($prefix = '')
    {
        $this['routes']->addCollection($this['controllers']->flush($prefix), $prefix);
    }

    /**
     * Redirects the user to another URL.
     *
     * @param string  $url    The URL to redirect to
     * @param integer $status The status code (302 by default)
     *
     * @see RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Creates a streaming response.
     *
     * @param mixed   $callback A valid PHP callback
     * @param integer $status   The response status code
     * @param array   $headers  An array of response headers
     *
     * @see StreamedResponse
     */
    public function stream($callback = null, $status = 200, $headers = array())
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Escapes a text for HTML.
     *
     * @param string  $text         The input text to be escaped
     * @param integer $flags        The flags (@see htmlspecialchars)
     * @param string  $charset      The charset
     * @param Boolean $doubleEncode Whether to try to avoid double escaping or not
     *
     * @return string Escaped text
     */
    public function escape($text, $flags = ENT_COMPAT, $charset = null, $doubleEncode = true)
    {
        return htmlspecialchars($text, $flags, $charset ?: $this['charset'], $doubleEncode);
    }

    /**
     * Convert some data into a JSON response.
     *
     * @param mixed   $data    The response data
     * @param integer $status  The response status code
     * @param array   $headers An array of response headers
     *
     * @see JsonResponse
     */
    public function json($data = array(), $status = 200, $headers = array())
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Mounts an application under the given route prefix.
     *
     * @param string                                           $prefix The route prefix
     * @param ControllerCollection|ControllerProviderInterface $app    A ControllerCollection or a ControllerProviderInterface instance
     */
    public function mount($prefix, $app)
    {
        if ($app instanceof ControllerProviderInterface) {
            $app = $app->connect($this);
        }

        if (!$app instanceof ControllerCollection) {
            throw new \LogicException('The "mount" method takes either a ControllerCollection or a ControllerProviderInterface instance.');
        }

        $this['routes']->addCollection($app->flush($prefix), $prefix);
    }

    /**
     * Handles the request and delivers the response.
     *
     * @param Request $request Request to process
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * {@inheritdoc}
     *
     * If you call this method directly instead of run(), you must call the
     * terminate() method yourself if you want the finish filters to be run.
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!$this->booted) {
            $this->boot();
        }

        $this->beforeDispatched = false;

        $current = HttpKernelInterface::SUB_REQUEST === $type ? $this['request'] : $this['request_error'];

        $this['request'] = $request;

        $this->flush();

        $response = $this['kernel']->handle($request, $type, $catch);

        $this['request'] = $current;

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this['kernel']->terminate($request, $response);
    }

    /**
     * Handles KernelEvents::REQUEST events registered early.
     *
     * @param GetResponseEvent $event The event to handle
     */
    public function onEarlyKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            if (isset($this['exception_handler'])) {
                $this['dispatcher']->addSubscriber($this['exception_handler']);
            }
            $this['dispatcher']->addSubscriber(new ResponseListener($this['charset']));
        }
    }

    /**
     * Runs before filters.
     *
     * @param GetResponseEvent $event The event to handle
     *
     * @see before()
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this['locale'] = $event->getRequest()->getLocale();

        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->beforeDispatched = true;
            $this['dispatcher']->dispatch(SilexEvents::BEFORE, $event);
        }

        $this['route_before_middlewares_trigger']($event);
    }

    /**
     * Handles converters.
     *
     * @param FilterControllerEvent $event The event to handle
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $route = $this['routes']->get($request->attributes->get('_route'));
        if ($route && $converters = $route->getOption('_converters')) {
            foreach ($converters as $name => $callback) {
                $request->attributes->set($name, call_user_func($callback, $request->attributes->get($name, null), $request));
            }
        }
    }

    /**
     * Handles string responses.
     *
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $response = $event->getControllerResult();
        $response = $response instanceof Response ? $response : new Response((string) $response);

        $event->setResponse($response);
    }

    /**
     * Runs after filters.
     *
     * @param FilterResponseEvent $event The event to handle
     *
     * @see after()
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $this['route_after_middlewares_trigger']($event);

        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this['dispatcher']->dispatch(SilexEvents::AFTER, $event);
        }
    }

    /**
     * Runs finish filters.
     *
     * @param PostResponseEvent $event The event to handle
     *
     * @see finish()
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this['dispatcher']->dispatch(SilexEvents::FINISH, $event);
    }

    /**
     * Runs error filters.
     *
     * Executes registered error handlers until a response is returned,
     * in which case it returns it to the client.
     *
     * @param GetResponseForExceptionEvent $event The event to handle
     *
     * @see error()
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$this->beforeDispatched) {
            $this->beforeDispatched = true;
            try {
                $this['dispatcher']->dispatch(SilexEvents::BEFORE, $event);
            } catch (\Exception $e) {
                // as we are already handling an exception, ignore this one
                // even if it might have been thrown on purpose by the developer
            }
        }

        $errorEvent = new GetResponseForExceptionEvent($this, $event->getRequest(), $event->getRequestType(), $event->getException());
        $this['dispatcher']->dispatch(SilexEvents::ERROR, $errorEvent);

        if ($errorEvent->hasResponse()) {
            $event->setResponse($errorEvent->getResponse());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST    => array(
                array('onEarlyKernelRequest', 256),
                array('onKernelRequest')
            ),
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE   => 'onKernelResponse',
            KernelEvents::EXCEPTION  => array('onKernelException', -10),
            KernelEvents::TERMINATE  => 'onKernelTerminate',
            KernelEvents::VIEW       => array('onKernelView', -10),
        );
    }
}
