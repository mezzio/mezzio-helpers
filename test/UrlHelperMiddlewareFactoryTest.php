<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Mezzio\Helper\Exception\MissingHelperException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperInterface;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Helper\UrlHelperMiddlewareFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(UrlHelperMiddlewareFactory::class)]
final class UrlHelperMiddlewareFactoryTest extends TestCase
{
    use AttributeAssertionsTrait;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function injectContainer(string $name, object $service): void
    {
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with($name)
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with($name)
            ->willReturn($service);
    }

    public function testFactoryCreatesAndReturnsMiddlewareWhenHelperIsPresentInContainer(): void
    {
        $helper = $this->createMock(UrlHelper::class);
        $this->injectContainer(UrlHelper::class, $helper);

        $factory    = new UrlHelperMiddlewareFactory();
        $middleware = $factory($this->container);

        self::assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        self::assertAttributeSame($helper, 'helper', $middleware);
    }

    public function testFactoryRaisesExceptionWhenContainerDoesNotContainHelper(): void
    {
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with(UrlHelper::class)
            ->willReturn(false);

        $factory = new UrlHelperMiddlewareFactory();

        $this->expectException(MissingHelperException::class);

        $factory($this->container);
    }

    public function testFactoryUsesUrlHelperServiceProvidedAtInstantiation(): void
    {
        $helper = $this->createMock(UrlHelper::class);
        $this->injectContainer('MyUrlHelper', $helper);
        $factory = new UrlHelperMiddlewareFactory('MyUrlHelper');

        $middleware = $factory($this->container);

        self::assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        self::assertAttributeSame($helper, 'helper', $middleware);
    }

    public function testFactoryAllowsSerialization(): void
    {
        $factory = UrlHelperMiddlewareFactory::__set_state([
            'urlHelperServiceName' => 'MyUrlHelper',
        ]);

        self::assertInstanceOf(UrlHelperMiddlewareFactory::class, $factory);
        self::assertAttributeSame('MyUrlHelper', 'urlHelperServiceName', $factory);
    }

    public function testFactoryAllowsCustomUrlHelperInterfaceImplementations(): void
    {
        $helper = $this->createMock(UrlHelperInterface::class);
        $this->injectContainer('MyUrlHelper', $helper);
        $factory = new UrlHelperMiddlewareFactory('MyUrlHelper');

        $middleware = $factory($this->container);

        self::assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        self::assertAttributeSame($helper, 'helper', $middleware);
    }
}
