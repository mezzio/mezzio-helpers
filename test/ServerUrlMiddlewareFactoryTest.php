<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Helper;

use Mezzio\Helper\Exception\MissingHelperException;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\ServerUrlMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class ServerUrlMiddlewareFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreatesAndReturnsMiddlewareWhenHelperIsPresentInContainer(): void
    {
        $helper    = $this->prophesize(ServerUrlHelper::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ServerUrlHelper::class)->willReturn(true);
        $container->get(ServerUrlHelper::class)->willReturn($helper->reveal());

        $factory    = new ServerUrlMiddlewareFactory();
        $middleware = $factory($container->reveal());
        $this->assertInstanceOf(ServerUrlMiddleware::class, $middleware);
    }

    public function testRaisesExceptionWhenContainerDoesNotContainHelper(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ServerUrlHelper::class)->willReturn(false);
        $container->has(\Zend\Expressive\Helper\ServerUrlHelper::class)->willReturn(false);

        $factory = new ServerUrlMiddlewareFactory();

        $this->expectException(MissingHelperException::class);
        $factory($container->reveal());
    }
}
