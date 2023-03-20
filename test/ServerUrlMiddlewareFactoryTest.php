<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Mezzio\Helper\Exception\MissingHelperException;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\ServerUrlMiddlewareFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function sprintf;

#[CoversClass(ServerUrlMiddlewareFactory::class)]
final class ServerUrlMiddlewareFactoryTest extends TestCase
{
    private ServerUrlMiddlewareFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ServerUrlMiddlewareFactory();
    }

    public function testCreatesAndReturnsMiddlewareWhenHelperIsPresentInContainer(): void
    {
        $helper    = $this->createMock(ServerUrlHelper::class);
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects(self::once())
            ->method('has')
            ->with(ServerUrlHelper::class)
            ->willReturn(true);

        $container
            ->expects(self::once())
            ->method('get')
            ->with(ServerUrlHelper::class)
            ->willReturn($helper);

        self::assertEquals(new ServerUrlMiddleware($helper), $this->factory->__invoke($container));
    }

    public function testRaisesExceptionWhenContainerDoesNotContainHelper(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects(self::once())
            ->method('has')
            ->with(ServerUrlHelper::class)
            ->willReturn(false);

        $container
            ->expects(self::never())
            ->method('get')
            ->with(ServerUrlHelper::class);

        $this->expectExceptionObject(new MissingHelperException(sprintf(
            '%s requires a %s service at instantiation; none found',
            ServerUrlMiddleware::class,
            ServerUrlHelper::class
        )));

        $this->factory->__invoke($container);
    }
}
