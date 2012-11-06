How to define Controllers as Services
=====================================

As your Silex application grows, you may wish to begin organizing your
controllers in a more formal fashion. Silex can use controller classes out of
the box, but with a bit of work, your controllers can be created as services,
giving you the full power of dependency injection and lazy loading.

.. ::todo Link above to controller classes cookbook

Example Application
-------------------

Here's a make believe blog application, we're going to change the
``/posts.json`` route to use a controller class, that is defined as a service::

    use Silex\Application;
    use Demo\Repository\PostRepository;

    $app = new Application;

    $app['posts.repository'] = $app->share(function() {
        return new PostRepository;
    });

    $app->get('/posts.json', function() use ($app) {
        return $app->json($app['posts.repository']->findAll());
    });

Controller Resolver
-------------------

By default, Silex uses Symfony's ``ControllerResolver`` to help convert
whatever was defined as the controller for the current route in to something it
can invoke. We want to override the default implementation, to allow for a format
taken from the full stack framework, whereby two strings separated by a single
colon, represent a service ID and the method to call on that service. For
example, ``posts.controller:indexJsonAction`` should resolve to the ``indexJsonAction``
method on the ``posts.controller`` service. Add the following class under your
app's namespace::

    namespace Demo\Controller;

    use Silex\ControllerResolver as BaseControllerResolver;

    class ControllerResolver extends BaseControllerResolver
    {
        protected function createController($controller)
        {
            if (false !== strpos($controller, '::')) {
                return parent::createController($controller);
            }

            if (false === strpos($controller, ':')) {
                throw new \LogicException(sprintf('Unable to parse the controller name "%s".', $controller));
            }

            list($service, $method) = explode(':', $controller, 2);

            if (!isset($this->app[$service])) {
                throw new \InvalidArgumentException(sprintf('Service "%s" does not exist.', $controller));
            }

            return array($this->app[$service], $method);
        }
    }

We then simply override Silex' built in ``resolver`` service with an instance of
our own ``ControllerResolver``::

    $app['resolver'] = $app->share(function () use ($app) {
        return new Demo\Controller\ControllerResolver($app, $app['logger']);
    });

Controller Implementation
-------------------------

Writing your controller is pretty simple, create a Plain Ol' PHP Object (POPO)
with your ``PostRepository`` as a dependency, along with a ``indexJsonAction`` method
to handle the request. Although not shown in the example below, you can use type
hinting and parameter naming to get the parameters you need, just like with
standard Silex routes.

If you are a TDD/BDD fan (and you should be), you may notice that this
controller has a well defined responsibility and is easily tested/specced::

    namespace Demo\Controller;

    use Demo\Repository\PostRepository;
    use Symfony\Component\HttpFoundation\JsonResponse;

    class PostController
    {
        protected $repo;

        public function __construct(PostRepository $repo)
        {
            $this->repo = $repo;
        }

        public function indexJsonAction()
        {
            return new JsonResponse($this->repo->findAll());
        }
    }

And lastly, define your controller as a service in the application::

    $app['posts.controller'] = $app->share(function() use ($app) {
        return new PostController($app['posts.repository']);
    });

    $app->get('/posts.json', "posts.controller:indexJsonAction");
