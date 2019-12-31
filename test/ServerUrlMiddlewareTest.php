<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\ServerUrlMiddleware;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class ServerUrlMiddlewareTest extends TestCase
{
    public function testMiddlewareInjectsHelperWithUri()
    {
        $uri = $this->prophesize(UriInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn($uri->reveal());
        $response = $this->prophesize(ResponseInterface::class);

        $helper = new ServerUrlHelper();
        $middleware = new ServerUrlMiddleware($helper);

        $invoked = false;
        $next = function ($req, $res) use (&$invoked) {
            $invoked = true;
            return $res;
        };

        $test = $middleware($request->reveal(), $response->reveal(), $next);
        $this->assertSame($response->reveal(), $test, 'Unexpected return value from middleware');
        $this->assertTrue($invoked, 'next() was not invoked');

        $this->assertAttributeSame(
            $uri->reveal(),
            'uri',
            $helper,
            'Helper was not injected with URI from request'
        );
    }
}
