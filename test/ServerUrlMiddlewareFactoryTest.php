<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Interop\Container\ContainerInterface;
use Mezzio\Helper\Exception\MissingHelperException;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\ServerUrlMiddlewareFactory;
use PHPUnit_Framework_TestCase as TestCase;

class ServerUrlMiddlewareFactoryTest extends TestCase
{
    public function testCreatesAndReturnsMiddlewareWhenHelperIsPresentInContainer()
    {
        $helper = $this->prophesize(ServerUrlHelper::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ServerUrlHelper::class)->willReturn(true);
        $container->get(ServerUrlHelper::class)->willReturn($helper->reveal());

        $factory = new ServerUrlMiddlewareFactory();
        $middleware = $factory($container->reveal());
        $this->assertInstanceOf(ServerUrlMiddleware::class, $middleware);
    }

    public function testRaisesExceptionWhenContainerDoesNotContainHelper()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ServerUrlHelper::class)->willReturn(false);
        $container->has(\Zend\Expressive\Helper\ServerUrlHelper::class)->willReturn(false);

        $factory = new ServerUrlMiddlewareFactory();

        $this->setExpectedException(MissingHelperException::class);
        $factory($container->reveal());
    }
}
