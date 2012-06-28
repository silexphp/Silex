SerializerServiceProvider
===========================

The *SerializerServiceProvider* provides a service for serializing objects.

Parameters
----------

None.

Services
--------

* **serializer**: An instance of `Symfony\Component\Serializer\Serializer
  <http://api.symfony.com/master/Symfony/Component/Serializer/Serializer.html>`_,
  using the `Symfony\Component\Serializer\Encoder\JsonEncoder
  <http://api.symfony.com/master/Symfony/Component/Serializer/Encoder/JsonEncoder.html>`_
  and `Symfony\Component\Serializer\Encoder\XmlEncoder
  <http://api.symfony.com/master/Symfony/Component/Serializer/Encoder/XmlEncoder>`_
  that is provided through the ``serializer.encoders`` service. 
  It also provides the default `Symfony\Component\Serializer\Normalizer\CustomNormalizer
  <http://api.symfony.com/master/Symfony/Component/Serializer/Normalizer/CustomNormalizer>`_
  and `Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer
  <http://api.symfony.com/master/Symfony/Component/Serializer/Normalizer/GetSetMethodNormalizer>`_
  that is provided through the ``serializer.normalizers`` service.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\SerializerServiceProvider());

Usage
-----

The ``SerializerServiceProvider`` provider provides a ``serializer`` service::

.. code-block:: php

    <?php
    
    use Silex\Application;
    use Silex\Provider\SerializerServiceProvider;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    
    $app = new Application();

    $app->register(new SerializerServiceProvider);
    
    $app->get("/api/pages/{id}.{_format}", function ($id) use ($app) {
        // assume a service that returns some page object with getters and setters
        $page = $app['page_repository']->find($id);
        $format = $app['request']->getFormat();

        if (!$page instance of Page) {
            throw new NotFoundHttpException("no page found for id: $id");
        }

        return new Response($app['serializer']->serialize($page, $format), array(
            "content-type" => $request->getMimeType($format)
        ));
    })->assert("_format", "xml|json")
      ->assert("id", "\d+");

