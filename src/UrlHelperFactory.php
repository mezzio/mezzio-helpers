<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Helper;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

class UrlHelperFactory
{
    /**
     * Create a UrlHelper instance.
     *
     * @throws Exception\MissingRouterException
     */
    public function __invoke(ContainerInterface $container) : UrlHelper
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

        return new UrlHelper($container->has(RouterInterface::class) ? $container->get(RouterInterface::class) : $container->get(\Zend\Expressive\Router\RouterInterface::class));
    }
}
