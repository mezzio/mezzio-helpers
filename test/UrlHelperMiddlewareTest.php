<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Router\RouteResult;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlHelperMiddlewareTest extends TestCase
{
    public function setUp()
    {
        $this->helper = $this->prophesize(UrlHelper::class);
    }

    public function createMiddleware()
    {
        return new UrlHelperMiddleware($this->helper->reveal());
    }

    public function testInvocationInjectsHelperWithRouteResultWhenPresentInRequest()
    {
        $routeResult = $this->prophesize(RouteResult::class)->reveal();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)->willReturn($routeResult);
        $this->helper->setRouteResult($routeResult)->shouldBeCalled();
        $response = $this->prophesize(ResponseInterface::class);
        $next = function ($req, $res) {
            return 'COMPLETE';
        };
        $middleware = $this->createMiddleware();
        $this->assertEquals('COMPLETE', $middleware(
            $request->reveal(),
            $response->reveal(),
            $next
        ));
    }

    public function testInvocationDoesNotInjectHelperWithRouteResultWhenAbsentInRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)->willReturn(false);
        $this->helper->setRouteResult(Argument::any())->shouldNotBeCalled();
        $response = $this->prophesize(ResponseInterface::class);
        $next = function ($req, $res) {
            return 'COMPLETE';
        };
        $middleware = $this->createMiddleware();
        $this->assertEquals('COMPLETE', $middleware(
            $request->reveal(),
            $response->reveal(),
            $next
        ));
    }
}
