<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

use function assert;
use function sprintf;

class UrlHelperFactory
{
    /**
     * Allow serialization
     *
     * @param array{basePath?: string, routerServiceName?: string} $data
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['basePath'] ?? '/',
            $data['routerServiceName'] ?? RouterInterface::class
        );
    }

    /**
     * Allows varying behavior per-instance.
     *
     * Defaults to '/' for the base path, and the FQCN of the RouterInterface.
     *
     * @param string $basePath Base path for the URL helper
     */
    public function __construct(
        private string $basePath = '/',
        private string $routerServiceName = RouterInterface::class
    ) {
    }

    /**
     * Create a UrlHelper instance.
     *
     * @throws Exception\MissingRouterException
     */
    public function __invoke(ContainerInterface $container): UrlHelper
    {
        if (! $container->has($this->routerServiceName)) {
            throw new Exception\MissingRouterException(sprintf(
                '%s requires a %s implementation; none found in container',
                UrlHelper::class,
                $this->routerServiceName
            ));
        }

        $router = $container->get($this->routerServiceName);
        assert($router instanceof RouterInterface);
        $helper = new UrlHelper($router);
        $helper->setBasePath($this->basePath);
        return $helper;
    }
}
