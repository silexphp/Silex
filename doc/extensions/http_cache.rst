HttpCacheExtension
==================

The *HttpCacheExtension* provides support for the Symfony2 Reverse Proxy.

Parameters
----------

* **http_cache.cache_dir**: The cache directory to store the HTTP cache data.

* **http_cache.options** (optional): An array of options for the `HttpCache
  <http://api.symfony.com/2.0/Symfony/Component/HttpKernel/HttpCache/HttpCache.html>`_
  constructor.

Services
--------

* **http_cache**: An instance of `HttpCache
  <http://api.symfony.com/2.0/Symfony/Component/HttpKernel/HttpCache/HttpCache.html>`_,

Registering
-----------

::

    $app->register(new Silex\Extension\HttpCacheExtension(), array(
        'http_cache.cache_dir' => __DIR__.'/cache/',
    ));

Usage
-----

Silex already supports any Reverse Proxy like Varnish out of the box by
setting Response HTTP cache headers::

    $app->get('/', function() {
        return new Response('Foo', 200, array(
            'Cache-Control' => 's-maxage=5',
        ));
    });

This extension allows you to use the Symfony2 reverse proxy natively with
Silex applications by using the `http_cache` service::

    $app['http_cache']->run();

The extension also provide ESI support::

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

For more information, consult the `Symfony2 HTTP Cache documentation
<http://symfony.com/doc/current/book/http_cache.html>`_.


Configuring you're dev. environment 
-----------------------------------
When setting up caching, please make sure that the following criterias has been 
configured properly as well, otherwise Silex and the caching module support might 
not work properly.

* **PHP**: session.cache_limiter
This should be set to an empty value, otherwise, PHP will send anti-caching headers.
In MAMP and WAMP, the default setting of this is to send anti-caching headers.

_For MAMP_ the default configuration file is located under 
/Applications/MAMP/conf/phpX.X/php.ini where X.X is the PHP version you're using.

_For WAMP_ the default configuration file is located uder
C:\wamp\conf\phpX.X\php.ini where X.X is the PHP version you're using.

* **Apache**: mod_cache, mod_disk_cach, mod_mem_cache
Verify you're Apache configuration for caching is set up properly 
as well, otherwise you might or might not get caching headers outputted as expected.

