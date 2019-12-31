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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface as DelegateInterface;

use const Webimpress\HttpMiddlewareCompatibility\HANDLER_METHOD;

class UrlHelperMiddlewareTest extends TestCase
{
    /**
     * @var UrlHelper|ObjectProphecy
     */
    private $helper;

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

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->{HANDLER_METHOD}(Argument::type(RequestInterface::class))->will(function ($req) {
            return 'COMPLETE';
        });

        $middleware = $this->createMiddleware();
        $this->assertEquals('COMPLETE', $middleware->process(
            $request->reveal(),
            $delegate->reveal()
        ));
    }

    public function testInvocationDoesNotInjectHelperWithRouteResultWhenAbsentInRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)->willReturn(false);
        $this->helper->setRouteResult(Argument::any())->shouldNotBeCalled();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->{HANDLER_METHOD}(Argument::type(RequestInterface::class))->will(function ($req) {
            return 'COMPLETE';
        });

        $middleware = $this->createMiddleware();
        $this->assertEquals('COMPLETE', $middleware->process(
            $request->reveal(),
            $delegate->reveal()
        ));
    }
}
