<?php

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('Silex\Tests', __DIR__);

Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(function($class) {
    if (0 === strpos(ltrim($class, '/'), 'JMS\SerializerBundle\Annotation')) {
        if (file_exists($file = dirname(__DIR__).'/vendor/jms/serializer-bundle/'.str_replace('\\', '/', $class).'.php')) {
            require_once $file;
        }
    }

    return class_exists($class, false);
});
