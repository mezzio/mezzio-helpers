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

class UrlHelperFactoryTest extends TestCase
{
    use AttributeAssertionsTrait;

    /** @var RouterInterface&MockObject */
    private $router;

    /** @var ContainerInterface&MockObject */
    private $container;

    private UrlHelperFactory $factory;

    public function setUp(): void
    {
        $this->router    = $this->createMock(RouterInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->factory = new UrlHelperFactory();
    }

    public function injectContainerService(string $name, object $service): void
    {
        $this->container->method('has')->with($name)->willReturn(true);
        $this->container->method('get')->with($name)->willReturn($service);
    }

    public function testFactoryReturnsHelperWithRouterInjected(): UrlHelper
    {
        $this->injectContainerService(RouterInterface::class, $this->router);

        $helper = ($this->factory)($this->container);
        $this->assertInstanceOf(UrlHelper::class, $helper);
        $this->assertAttributeSame($this->router, 'router', $helper);
        return $helper;
    }

    /**
     * @depends testFactoryReturnsHelperWithRouterInjected
     */
    public function testHelperUsesDefaultBasePathWhenNoneProvidedAtInstantiation(UrlHelper $helper): void
    {
        $this->assertEquals('/', $helper->getBasePath());
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

        $this->assertInstanceOf(UrlHelper::class, $helper);
        $this->assertAttributeSame($this->router, 'router', $helper);
        $this->assertEquals('/api', $helper->getBasePath());
    }

    public function testFactoryAllowsSerialization(): void
    {
        $factory = UrlHelperFactory::__set_state([
            'basePath'          => '/api',
            'routerServiceName' => Router::class,
        ]);

        $this->assertInstanceOf(UrlHelperFactory::class, $factory);
        $this->assertAttributeSame('/api', 'basePath', $factory);
        $this->assertAttributeSame(Router::class, 'routerServiceName', $factory);
    }
}
