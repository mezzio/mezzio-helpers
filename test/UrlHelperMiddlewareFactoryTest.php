<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Mezzio\Helper\Exception\MissingHelperException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Helper\UrlHelperMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class UrlHelperMiddlewareFactoryTest extends TestCase
{
    use AttributeAssertionsTrait;
    use ProphecyTrait;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function injectContainer(string $name, object $service): void
    {
        $service = $service instanceof ObjectProphecy ? $service->reveal() : $service;
        $this->container->has($name)->willReturn(true);
        $this->container->get($name)->willReturn($service);
    }

    public function testFactoryCreatesAndReturnsMiddlewareWhenHelperIsPresentInContainer(): void
    {
        $helper = $this->prophesize(UrlHelper::class)->reveal();
        $this->injectContainer(UrlHelper::class, $helper);

        $factory    = new UrlHelperMiddlewareFactory();
        $middleware = $factory($this->container->reveal());
        $this->assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        $this->assertAttributeSame($helper, 'helper', $middleware);
    }

    public function testFactoryRaisesExceptionWhenContainerDoesNotContainHelper(): void
    {
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has(\zend\expressive\helper\urlhelper::class)->willReturn(false);
        $factory = new UrlHelperMiddlewareFactory();
        $this->expectException(MissingHelperException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryUsesUrlHelperServiceProvidedAtInstantiation(): void
    {
        $helper = $this->prophesize(UrlHelper::class)->reveal();
        $this->injectContainer(MyUrlHelper::class, $helper);
        $factory = new UrlHelperMiddlewareFactory(MyUrlHelper::class);

        $middleware = $factory($this->container->reveal());

        $this->assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        $this->assertAttributeSame($helper, 'helper', $middleware);
    }

    public function testFactoryAllowsSerialization(): void
    {
        $factory = UrlHelperMiddlewareFactory::__set_state([
            'urlHelperServiceName' => MyUrlHelper::class,
        ]);

        $this->assertInstanceOf(UrlHelperMiddlewareFactory::class, $factory);
        $this->assertAttributeSame(MyUrlHelper::class, 'urlHelperServiceName', $factory);
    }
}
