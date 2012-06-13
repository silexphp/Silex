HttpCacheServiceProvider
========================

The *HttpCacheProvider* provides support for the Symfony2 Reverse Proxy.

Parameters
----------

* **http_cache.cache_dir**: The cache directory to store the HTTP cache data.

* **http_cache.options** (optional): An array of options for the `HttpCache
  <http://api.symfony.com/master/Symfony/Component/HttpKernel/HttpCache/HttpCache.html>`_
  constructor.

Services
--------

* **http_cache**: An instance of `HttpCache
  <http://api.symfony.com/master/Symfony/Component/HttpKernel/HttpCache/HttpCache.html>`_.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
        'http_cache.cache_dir' => __DIR__.'/cache/',
    ));

Usage
-----

Silex already supports any reverse proxy like Varnish out of the box by
setting Response HTTP cache headers::

    use Symfony\Component\HttpFoundation\Response;

    $app->get('/', function() {
        return new Response('Foo', 200, array(
            'Cache-Control' => 's-maxage=5',
        ));
    });

.. tip::

    If you want Silex to trust the ``X-Forwarded-For*`` headers from your
    reverse proxy, you will need to run your application like this::

        use Symfony\Component\HttpFoundation\Request;

        Request::trustProxyData();
        $app->run();

This provider allows you to use the Symfony2 reverse proxy natively with
Silex applications by using the ``http_cache`` service::

    $app['http_cache']->run();

The provider also provides ESI support::

    $app->get('/', function() {
        return new Response(<<<EOF
    <html>
        <body>
            Hello
            <esi:include src="/included" />
        </body>
    </html>

    EOF
        , 200, array(
            'Cache-Control' => 's-maxage=20',
            'Surrogate-Control' => 'content="ESI/1.0"',
        ));
    });

    $app->get('/included', function() {
        return new Response('Foo', 200, array(
            'Cache-Control' => 's-maxage=5',
        ));
    });

    $app['http_cache']->run();

.. tip::

    To help you debug caching issues, set your application ``debug`` to true.
    Symfony automatically adds a ``X-Symfony-Cache`` header to each response
    with useful information about cache hits and misses.

    If you are *not* using the Symfony Session provider, you might want to set
    the PHP ``session.cache_limiter`` setting to an empty value to avoid the
    default PHP behavior.

    Finally, check that your Web server does not override your caching strategy.

For more information, consult the `Symfony2 HTTP Cache documentation
<http://symfony.com/doc/current/book/http_cache.html>`_.
