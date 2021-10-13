<?php

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
        if (! $container->has(ServerUrlHelper::class)) {
            throw new Exception\MissingHelperException(sprintf(
                '%s requires a %s service at instantiation; none found',
                ServerUrlMiddleware::class,
                ServerUrlHelper::class
            ));
        }

        return new ServerUrlMiddleware($container->get(ServerUrlHelper::class));
    }
}
