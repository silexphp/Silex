How to use controllers like in Symfony2 framework
=================================================

In Silex, the most common way to define the controller of a route is with a
closure. But when your project gets bigger, you want to organize your code in
classes. You can use the syntax ``ClassName::methodName`` in your route
definition instead of a ``function () { ... }`` but you have to inject the
``Silex\Application $app`` as a parameter on each method of your controller
class, which can become quite boring.

In order to avoid these repetitive injections, you need to your own
``ControllerResolver`` which extends the ``Silex\ControllerResolver``
(inspired by the one in the Symfony2 framework bundle) :

.. code-block:: php

	namespace MyProject\Controller;

	use Silex\ControllerResolver as SilexControllerResolver;

	class ControllerResolver extends SilexControllerResolver
	{
	    /**
	     * Returns a callable for the given controller.
	     *
	     * @param string $controller A Controller string
	     *
	     * @return mixed A PHP callable
	     */
	    protected function createController($controller)
	    {
	        if (false === strpos($controller, '::')) {
	            throw new \InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
	        }

	        list($class, $method) = explode('::', $controller, 2);

	        if (!class_exists($class)) {
	            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
	        }

	        $controller = new $class();
	        if ($controller instanceof AbstractController) {
	            $controller->setContainer($this->app);
	        }

	        return array($controller, $method);
	    }
	}

The code is pretty straightforward : you create a ``new $class()`` and if it is
an instance of ``AbstractController`` you inject the Silex ``Application``.

Now the ``AbstractController`` is easy to create :

.. code-block:: php

	namespace MyProject\Controller;

	use Silex\Application;

	abstract class AbstractController
	{
	    /**
	     * @var Application;
	     */
	    protected $app = null;

	    /**
	     * @param Application $app
	     */
	    public function setContainer(Application $app)
	    {
	        $this->app = $app;
	    }
	}

Finally, you replace the ``Silex\ControllerResolver`` by your own in your
bootstrap file :

.. code-block:: php

	$app['resolver'] = $app->share(function () use ($app) {
	    return new \MyProject\ControllerResolver($app);
	});

And that's all. You only have to make all your controller's class extend
``AbstractController``.