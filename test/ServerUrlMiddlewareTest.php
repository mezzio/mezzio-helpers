<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class ServerUrlMiddlewareTest extends TestCase
{
    public function testMiddlewareInjectsHelperWithUri()
    {
        $uri = $this->prophesize(UriInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn($uri->reveal());

        $helper = new ServerUrlHelper();
        $middleware = new ServerUrlMiddleware($helper);

        $invoked = false;

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process(Argument::type(RequestInterface::class))->will(function ($req) use (&$invoked) {
            $invoked = true;

            return new Response();
        });

        $test = $middleware->process($request->reveal(), $delegate->reveal());
        //$this->assertSame($response->reveal(), $test, 'Unexpected return value from middleware');
        $this->assertTrue($invoked, 'next() was not invoked');

        $this->assertAttributeSame(
            $uri->reveal(),
            'uri',
            $helper,
            'Helper was not injected with URI from request'
        );
    }
}
