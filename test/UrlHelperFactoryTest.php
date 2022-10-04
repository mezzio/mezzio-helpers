<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Mezzio\Helper\Exception\MissingRouterException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/** @covers \Mezzio\Helper\UrlHelperFactory */
final class UrlHelperFactoryTest extends TestCase
{
    use AttributeAssertionsTrait;

    /** @var RouterInterface&MockObject */
    private RouterInterface $router;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private UrlHelperFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router    = $this->createMock(RouterInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->factory = new UrlHelperFactory();
    }

    public function injectContainerService(string $name, object $service): void
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

    public function testFactoryReturnsHelperWithRouterInjected(): UrlHelper
    {
        $this->injectContainerService(RouterInterface::class, $this->router);

        $helper = ($this->factory)($this->container);

        self::assertInstanceOf(UrlHelper::class, $helper);
        self::assertAttributeSame($this->router, 'router', $helper);

        return $helper;
    }

    /**
     * @depends testFactoryReturnsHelperWithRouterInjected
     */
    public function testHelperUsesDefaultBasePathWhenNoneProvidedAtInstantiation(UrlHelper $helper): void
    {
        self::assertSame('/', $helper->getBasePath());
    }

    public function testFactoryRaisesExceptionWhenRouterIsNotPresentInContainer(): void
    {
        $this->expectException(MissingRouterException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryUsesBasePathAndRouterServiceProvidedAtInstantiation(): void
    {
        $this->injectContainerService(Router::class, $this->router);
        $factory = new UrlHelperFactory('/api', Router::class);

        $helper = $factory($this->container);

        self::assertInstanceOf(UrlHelper::class, $helper);
        self::assertAttributeSame($this->router, 'router', $helper);
        self::assertSame('/api', $helper->getBasePath());
    }

    public function testFactoryAllowsSerialization(): void
    {
        $factory = UrlHelperFactory::__set_state([
            'basePath'          => '/api',
            'routerServiceName' => Router::class,
        ]);

        self::assertInstanceOf(UrlHelperFactory::class, $factory);
        self::assertAttributeSame('/api', 'basePath', $factory);
        self::assertAttributeSame(Router::class, 'routerServiceName', $factory);
    }
}
