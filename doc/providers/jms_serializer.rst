JMSSerializerServiceProvider
============================

The ``JMSSerializerServiceProvider`` provides a service for serializing objects.
This service provider uses the `JMS\SerializerBundle
<http://jmsyst.com/bundles/JMSSerializerBundle>`_ for serializing.

Parameters
----------

* **serializer.cache.directory**: The directory to use for storing the metadata
  cache.

* **serializer.naming_strategy.seperator** (optional): The separator string used
  when normalizing properties.

* **serializer.naming_strategy.lower_case** (optional): Boolean flag indicating
  if the properties should be normalized as lower case strings.

* **serializer.date_time_handler.format** (optional): The format used to
  serialize and deserialize *DateTime* objects. Refer to the `PHP documentation
  for supported Date/Time formats <http://php.net/manual/en/datetime.formats.php>`_

* **serializer.date_time_handler.default_timezone** (optional): The timezone to
  use when serializing and deserializing *DateTime* objects. Refer to the `PHP
  documentation for a list of supported timezones
  <http://php.net/manual/en/timezones.php>`_

* **serializer.disable_external_entities** (optional): Boolean flag indicating
  if the serializer should disable external entities for the XML serialization
  format.

Services
--------

* **serializer**: An instance of *JMS\SerializerBundle\Serializer\Serializer*.

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\JMSSerializerServiceProvider());

Usage
-----

Annotate the class you wish to serialize, refer to the `annotation documentation
<http://jmsyst.com/bundles/JMSSerializerBundle/master/reference/annotations>`::

.. code-block:: php

    <?php

    use JMS\SerializerBundle\Annotation;

    // The serializer bundle doesn't need getters or setters
    class Page
    {
        /**
         * @Type("integer")
         */
        private $id;

        /**
         * @Type("string")
         */
        private $title;

        /**
         * @Type("string")
         */
        private $body;

        /**
         * @Type("DateTime")
         */
        private $created;

        /**
         * @Type("Author")
         */
        private $author;

        /**
         * @Type("boolean")
         */
        private $featured;
    }

    class Author
    {
        /**
         * @Type("string")
         */
        private $name;
    }

The ``JMSSerializerServiceProvider`` provider provides a ``serializer`` service.
Use it in your application to serialize and deserialize your objects::

.. code-block:: php

    <?php

    use Silex\Application;
    use Silex\Provider\JMSSerializerServiceProvider;
    use Symfony\Component\HttpFoundation\Response;

    $app = new Application();

    // Make sure that the PHP script can write in the cache directory and that
    // the directory exists
    $app->register(new JMSSerializerServiceProvider(), array(
        'serializer.cache.directory' => __DIR__."/cache/serializer"
    ));

    // only accept content types supported by the serializer via the assert method.
    $app->get("/pages/{id}.{_format}", function ($id) use ($app) {
        // assume a page_repository service exists that returns Page objects.
        $page = $app['page_repository']->find($id);
        $format = $app['request']->getFormat();

        if (!$page instanceof Page) {
            $this->abort("No page found for id: $id");
        }

        return new Response($app['serializer']->serialize($page, $format), 200, array(
            "Content-Type" => $app['request']->getMimeType($format)
        ));
    })->assert("_format", "xml|json")
      ->assert("id", "\d+");
