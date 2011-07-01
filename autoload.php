<?php

if (false === class_exists('Symfony\Component\ClassLoader\UniversalClassLoader', false)) {
    require_once __DIR__.'/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';
}

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony' => __DIR__.'/vendor',
    'Silex'   => __DIR__.'/src',
));
$loader->registerPrefixes(array(
    'Pimple' => __DIR__.'/vendor/pimple/lib',
));
$loader->register();
