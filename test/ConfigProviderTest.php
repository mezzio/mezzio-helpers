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
use Mezzio\Helper\UrlHelperInterface;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Helper\UrlHelperMiddlewareFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function testReturnedArrayContainsDependencies(): void
    {
        $config = (new ConfigProvider())();

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
                'aliases'    => [
                    UrlHelperInterface::class => UrlHelper::class,
                ],
            ],
        ], $config);
    }
}
