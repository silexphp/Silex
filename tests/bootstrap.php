<?php

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('Silex\Tests', __DIR__);

if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('IntlDateFormatter', __DIR__.'/../vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs');
    $loader->add('Collator', __DIR__.'/../vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs');
    $loader->add('Locale', __DIR__.'/../vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs');
    $loader->add('NumberFormatter', __DIR__.'/../vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs');
}
