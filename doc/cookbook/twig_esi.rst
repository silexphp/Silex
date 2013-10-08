Using ESI with Twig
===================

Edge Side Includes (ESI) is a powerful feature that lets you cache parts of a
template independently of the rest of the layout when interacting with a reverse
proxy, like Varnish or Symfony's own HttpCache. This can be utilised in Twig
templates to make :doc:`sub requests </cookbook/sub_requests>` cacheable.

All you need to make ESI works with Twig templates in Silex is provided by the
``HttpCacheServiceProvider`` and Twig ``HttpKernelExtension``, but you need to
add a bit of wiring to enable this feature:

.. code-block:: php

	use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
	use Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer;
	use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
	use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;

	$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
	    $inlineFragmentRenderer = new InlineFragmentRenderer($app['kernel'], $app['dispatcher']);
	    $fragmentRenderer = new EsiFragmentRenderer($app['http_cache.esi'], $inlineFragmentRenderer);
	    $fragmentHandler = new FragmentHandler(array($fragmentRenderer, $inlineFragmentRenderer), false);
	    $fragmentHandler->setRequest($app['request']);
	    $twig->addExtension(new HttpKernelExtension($fragmentHandler));

	    return $twig;
	}));

You can now use ``render_esi`` in your templates:

.. code-block:: jinja

  <h1>{{ article.title }}</h1>
  {{ article.body }}

  <h2>Latest articles:</h2>
  {{ render_esi('/latest-articles-widget') }}

Making Your Controllers ESI ready
---------------------------------

To reap the benefits of ESI, your controllers need to add a special header:

.. code-block:: php

	$response = new Response('My cacheable content');
	$response->setMaxAge(3600)->setPublic();
	$response->headers->set('Surrogate-Control', 'content="ESI/1.0"');


As you're quite likely to want to add this cache header to all your ESI responses,
Symfony provides an ``EsiListener``:

.. code-block:: php

	use Symfony\Component\HttpKernel\EventListener\EsiListener;

	$app['dispatcher']->addSubscriber(new EsiListener($app['http_cache.esi']));

This adds the Surrogate-Control to all responses that contain ESI tags.
