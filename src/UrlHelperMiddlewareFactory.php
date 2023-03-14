<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use Psr\Container\ContainerInterface;

use function assert;
use function sprintf;

class UrlHelperMiddlewareFactory
{
    /**
     * Allow serialization
     *
     * @param array{urlHelperServiceName?: string} $data
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['urlHelperServiceName'] ?? UrlHelper::class
        );
    }

    /**
     * Allow varying behavior based on URL helper service name.
     */
    public function __construct(private string $urlHelperServiceName = UrlHelper::class)
    {
    }

    /**
     * Create and return a UrlHelperMiddleware instance.
     *
     * @throws Exception\MissingHelperException If the UrlHelper service is missing.
     */
    public function __invoke(ContainerInterface $container): UrlHelperMiddleware
    {
        if (! $container->has($this->urlHelperServiceName)) {
            throw new Exception\MissingHelperException(sprintf(
                '%s requires a %s service at instantiation; none found',
                UrlHelperMiddleware::class,
                $this->urlHelperServiceName
            ));
        }

        $helper = $container->get($this->urlHelperServiceName);
        assert($helper instanceof UrlHelperInterface);

        return new UrlHelperMiddleware($helper);
    }
}
