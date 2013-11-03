Managing Assets in Templates
============================

A Silex application is not always hosted at the web root directory. To avoid
repeating the base path whenever you link to another page, it is highly
recommended to use the :doc:`URL generator service provider
</providers/url_generator>`.

But what about images, stylesheets, or JavaScript files? Their URLs are not
managed by the Silex router, but nonetheless, they need to get prefixed by the
base path. Fortunately, the Request object contain the application base path
that needs to be prepended::

    // generate a link to the stylesheets in /css/styles.css
    $request->getBasePath().'/css/styles.css';

And doing the same in a Twig template is as easy as it can get:

.. code-block:: jinja

    {{ app.request.basepath }}/css/styles.css

If your assets are hosted under a different host, you might want to abstract
the path by defining a Silex parameter::

    $app['asset_path'] = 'http://assets.examples.com';

Using it in a template is as easy as before:

.. code-block:: jinja

    {{ app.asset_path }}/css/styles.css

If you need to implement some logic independently of the asset, define a
service instead::

    $app['asset_path'] = $app->share(function () {
        // implement whatever logic you need to determine the asset path

        return 'http://assets.examples.com';
    });

Usage is exactly the same as before:

.. code-block:: jinja

    {{ app.asset_path }}/css/styles.css

If the asset location depends on the asset type or path, you will need more
abstraction; here is one way to do that with a Twig function::

    $app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
        $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) {
            // implement whatever logic you need to determine the asset path

            return sprintf('http://assets.examples.com/%s', ltrim($asset, '/'));
        }));

        return $twig;
    }));

The ``asset`` function can then be used in your templates:

.. code-block:: jinja

    {{ asset('/css/styles.css') }}
