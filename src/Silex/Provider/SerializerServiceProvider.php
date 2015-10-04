<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Symfony Serializer component Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Marijn Huizendveld <marijn@pink-tie.com>
 */
class SerializerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     *
     * This method registers a serializer service. {@link http://api.symfony.com/master/Symfony/Component/Serializer/Serializer.html
     * The service is provided by the Symfony Serializer component}.
     */
    public function register(Container $app)
    {
        $app['serializer'] = function ($app) {
            return new Serializer($app['serializer.normalizers'], $app['serializer.encoders']);
        };

        $app['serializer.encoders'] = function () {
            return array(new JsonEncoder(), new XmlEncoder());
        };

        $app['serializer.normalizers'] = function () {
            return array(new CustomNormalizer(), new GetSetMethodNormalizer());
        };
    }
}
