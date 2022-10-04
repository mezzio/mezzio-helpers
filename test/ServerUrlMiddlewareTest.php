<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Helper\ServerUrlMiddleware */
final class ServerUrlMiddlewareTest extends TestCase
{
    public function testMiddlewareInjectsHelperWithUri(): void
    {
        $uri     = $this->createMock(UriInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::once())
            ->method('getUri')
            ->willReturn($uri);

        $helper = $this->createMock(ServerUrlHelper::class);
        $helper
            ->expects(self::once())
            ->method('setUri')
            ->with($uri);

        $middleware = new ServerUrlMiddleware($helper);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        self::assertSame($response, $middleware->process($request, $handler));
    }
}
