<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex;

use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds Application as a valid argument for controllers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerResolver extends BaseControllerResolver
{
    protected $app;

    /**
     * Constructor.
     *
     * @param Application     $app    An Application instance
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(Application $app, LoggerInterface $logger = null)
    {
        $this->app = $app;

        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function getController(Request $request)
    {
        // Do we have Route Middlewares to handle ?
        if ($request->attributes->has('_middlewares')) {
            $middlewares = $request->attributes->get('_middlewares');
            foreach ($middlewares as $currentMiddleware) {
                // This is where we trigger the Route Middlewares
                call_user_func( $currentMiddleware );
            }
        }
        // Return parent implementation result
        return parent::getController($request);
    }

    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        foreach ($parameters as $param) {
            if ($param->getClass() && $param->getClass()->isInstance($this->app)) {
                $request->attributes->set($param->getName(), $this->app);

                break;
            }
        }

        return parent::doGetArguments($request, $controller, $parameters);
    }
}
