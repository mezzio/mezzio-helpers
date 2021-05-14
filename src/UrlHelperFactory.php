<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

class UrlHelperFactory
{
    /** @var string Base path for the URL helper */
    private $basePath;

    /** @var string $routerServiceName */
    private $routerServiceName;

    /**
     * Allow serialization
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
     */
    public function __construct(string $basePath = '/', string $routerServiceName = RouterInterface::class)
    {
        $this->basePath          = $basePath;
        $this->routerServiceName = $routerServiceName;
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

        $helper = new UrlHelper($container->get($this->routerServiceName));
        $helper->setBasePath($this->basePath);
        return $helper;
    }
}
