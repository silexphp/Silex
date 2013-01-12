Organizing Controllers
======================

A more OOP way to organizing, in this way, it will not create global variable
(P.S. Sorry for my bad english)::

    // Controller/User.php
    namespace Controller;
    
    use Silex\Application;
    
    class User extends \Silex\ControllerCollection {
        public function __construct(Application $app) {
            parent::__construct($app['route_factory']);
            $this->get('/', function () {
                return 'User home page';
            }
        }
    }

    // index.php
    include 'Controller\User.php'; // Don't need if you have set autoload
    $app->mount('/user', new Controller\User($app));

When your application starts to define too many controllers, you might want to
group them logically::

    // define controllers for a blog
    $blog = $app['controllers_factory'];
    $blog->get('/', function () {
        return 'Blog home page';
    });
    // ...

    // define controllers for a forum
    $forum = $app['controllers_factory'];
    $forum->get('/', function () {
        return 'Forum home page';
    });

    // define "global" controllers
    $app->get('/', function () {
        return 'Main home page';
    });

    $app->mount('/blog', $blog);
    $app->mount('/forum', $forum);

.. note::

    ``$app['controllers_factory']`` is a factory that returns a new instance
    of ``ControllerCollection`` when used.

``mount()`` prefixes all routes with the given prefix and merges them into the
main Application. So, ``/`` will map to the main home page, ``/blog/`` to the
blog home page, and ``/forum/`` to the forum home page.

.. caution::

    When mounting a route collection under ``/blog``, it is not possible to
    define a route for the ``/blog`` URL. The shortest possible URL is
    ``/blog/``.

.. note::

    When calling ``get()``, ``match()``, or any other HTTP methods on the
    Application, you are in fact calling them on a default instance of
    ``ControllerCollection`` (stored in ``$app['controllers']``).

Another benefit is the ability to apply settings on a set of controllers very
easily. Building on the example from the middleware section, here is how you
would secure all controllers for the backend collection::

    $backend = $app['controllers_factory'];

    // ensure that all controllers require logged-in users
    $backend->before($mustBeLogged);

.. tip::

    For a better readability, you can split each controller collection into a
    separate file::

        // blog.php
        $blog = $app['controllers_factory'];
        $blog->get('/', function () { return 'Blog home page'; });

        return $blog;

        // app.php
        $app->mount('/blog', include 'blog.php');

    Instead of requiring a file, you can also create a :doc:`Controller
    provider </providers#controllers-providers>`.
