<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Interop\Container\ContainerInterface;
use Mezzio\Helper\Exception\MissingRouterException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit_Framework_TestCase as TestCase;

class UrlHelperFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->router = $this->prophesize(RouterInterface::class);
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->factory = new UrlHelperFactory();
    }

    public function injectContainerService($name, $service)
    {
        $this->container->has($name)->willReturn(true);
        $this->container->get($name)->willReturn($service);
    }

    public function testFactoryReturnsHelperWithRouterInjected()
    {
        $this->injectContainerService(RouterInterface::class, $this->router->reveal());

        $helper = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(UrlHelper::class, $helper);
        $this->assertAttributeSame($this->router->reveal(), 'router', $helper);
    }

    public function testFactoryRaisesExceptionWhenRouterIsNotPresentInContainer()
    {
        $this->setExpectedException(MissingRouterException::class);
        $this->factory->__invoke($this->container->reveal());
    }
}
