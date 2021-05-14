<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Laminas\Diactoros\Response;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;

class ServerUrlMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    public function testMiddlewareInjectsHelperWithUri(): void
    {
        $uri     = $this->prophesize(UriInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn($uri->reveal());

        $helper     = new ServerUrlHelper();
        $middleware = new ServerUrlMiddleware($helper);

        $invoked = false;

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(RequestInterface::class))->will(function ($req) use (&$invoked) {
            $invoked = true;

            return new Response();
        });

        $test = $middleware->process($request->reveal(), $handler->reveal());
        //$this->assertSame($response->reveal(), $test, 'Unexpected return value from middleware');
        $this->assertTrue($invoked, 'next() was not invoked');

        $r = new ReflectionProperty($helper, 'uri');
        $r->setAccessible(true);
        self::assertSame($uri->reveal(), $r->getValue($helper), 'Helper was not injected with URI from request');
    }
}
