<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Router\RouteResultSubjectInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlHelperMiddlewareTest extends TestCase
{
    public function setUp()
    {
        $this->application = $this->prophesize(RouteResultSubjectInterface::class);
        $this->helper = $this->prophesize(UrlHelper::class);
    }

    public function createMiddleware()
    {
        return new UrlHelperMiddleware(
            $this->helper->reveal(),
            $this->application->reveal()
        );
    }

    public function testInvocationRegistersHelperAsObserverOnRouteResultSubject()
    {
        $this->application
            ->attachRouteResultObserver($this->helper->reveal())
            ->shouldBeCalled();
        $request = $this->prophesize(ServerRequestInterface::class);
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
