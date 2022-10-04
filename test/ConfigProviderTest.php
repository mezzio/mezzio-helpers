<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Mezzio\Helper\ConfigProvider;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\ServerUrlMiddlewareFactory;
use Mezzio\Helper\Template\TemplateVariableContainerMiddleware;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperFactory;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Helper\UrlHelperMiddlewareFactory;
use PHPUnit\Framework\TestCase;

/** @covers \Mezzio\Helper\ConfigProvider */
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray(): array
    {
        $config = ($this->provider)();

        self::assertIsArray($config);

        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     */
    public function testReturnedArrayContainsDependencies(array $config): void
    {
        self::assertSame([
            'dependencies' => [
                'invokables' => [
                    ServerUrlHelper::class => ServerUrlHelper::class,
                    TemplateVariableContainerMiddleware::class
                    => TemplateVariableContainerMiddleware::class,
                ],
                'factories'  => [
                    ServerUrlMiddleware::class => ServerUrlMiddlewareFactory::class,
                    UrlHelper::class           => UrlHelperFactory::class,
                    UrlHelperMiddleware::class => UrlHelperMiddlewareFactory::class,
                ],
            ],
        ], $config);
    }
}
