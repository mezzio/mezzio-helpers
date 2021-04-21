<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Helper;

use Psr\Container\ContainerInterface;

use function sprintf;

class ServerUrlMiddlewareFactory
{
    /**
     * Create a ServerUrlMiddleware instance.
     *
     * @throws Exception\MissingHelperException
     */
    public function __invoke(ContainerInterface $container): ServerUrlMiddleware
    {
        if (
            ! $container->has(ServerUrlHelper::class)
            && ! $container->has(\Zend\Expressive\Helper\ServerUrlHelper::class)
        ) {
            throw new Exception\MissingHelperException(sprintf(
                '%s requires a %s service at instantiation; none found',
                ServerUrlMiddleware::class,
                ServerUrlHelper::class
            ));
        }

        return new ServerUrlMiddleware(
            $container->has(ServerUrlHelper::class)
                ? $container->get(ServerUrlHelper::class)
                : $container->get(\Zend\Expressive\Helper\ServerUrlHelper::class)
        );
    }
}
