<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper;

use Interop\Container\ContainerInterface;
use Mezzio\Application;
use Mezzio\Router\RouterInterface;

class UrlHelperFactory
{
    /**
     * Create a UrlHelper instance.
     *
     * @param ContainerInterface $container
     * @return UrlHelper
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(RouterInterface::class)
            && ! $container->has(\Zend\Expressive\Router\RouterInterface::class)
        ) {
            throw new Exception\MissingRouterException(sprintf(
                '%s requires a %s implementation; none found in container',
                UrlHelper::class,
                RouterInterface::class
            ));
        }

        $helper = new UrlHelper($container->has(RouterInterface::class) ? $container->get(RouterInterface::class) : $container->get(\Zend\Expressive\Router\RouterInterface::class));

        if ($container->has(Application::class)) {
            $application = $container->get(Application::class);
            $application->attachRouteResultObserver($helper);
        }

        return $helper;
    }
}
