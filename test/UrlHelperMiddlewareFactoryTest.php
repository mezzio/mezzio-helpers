<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Interop\Container\ContainerInterface;
use Mezzio\Helper\Exception\MissingHelperException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Helper\UrlHelperMiddlewareFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class UrlHelperMiddlewareFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function injectContainer($name, $service)
    {
        $service = $service instanceof ObjectProphecy ? $service->reveal() : $service;
        $this->container->has($name)->willReturn(true);
        $this->container->get($name)->willReturn($service);
    }

    public function testFactoryCreatesAndReturnsMiddlewareWhenHelperIsPresentInContainer()
    {
        $helper = $this->prophesize(UrlHelper::class)->reveal();
        $this->injectContainer(UrlHelper::class, $helper);

        $factory = new UrlHelperMiddlewareFactory();
        $middleware = $factory($this->container->reveal());
        $this->assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        $this->assertAttributeSame($helper, 'helper', $middleware);
    }

    public function testFactoryRaisesExceptionWhenContainerDoesNotContainHelper()
    {
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Helper\UrlHelper::class)->willReturn(false);
        $this->injectContainer(
            RouteResultSubjectInterface::class,
            $this->prophesize(RouteResultSubjectInterface::class)
        );
        $factory = new UrlHelperMiddlewareFactory();
        $this->setExpectedException(MissingHelperException::class);
        $middleware = $factory($this->container->reveal());
    }
}
