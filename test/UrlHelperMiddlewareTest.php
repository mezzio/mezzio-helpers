<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperInterface;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(UrlHelperMiddleware::class)]
final class UrlHelperMiddlewareTest extends TestCase
{
    /** @var UrlHelperInterface&MockObject */
    private UrlHelperInterface $helper;

    private UrlHelperMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = $this->createMock(UrlHelperInterface::class);

        $this->middleware = new UrlHelperMiddleware($this->helper);
    }

    public function testInvocationInjectsHelperWithRequest(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $routeResult = RouteResult::fromRoute(new Route(
            '/foo',
            $this->createMock(MiddlewareInterface::class),
        ));

        $request = $this->createMock(ServerRequestInterface::class);

        $this->helper
            ->expects(self::never())
            ->method('setRouteResult')
            ->with($routeResult);

        $this->helper
            ->expects(self::once())
            ->method('setRequest')
            ->with($request);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        self::assertSame($response, $this->middleware->process($request, $handler));
    }

    public function testCanHandleMultipleSubsequentRequests(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $handler  = new class ($response) implements RequestHandlerInterface {
            public function __construct(
                private readonly ResponseInterface $response,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $helper     = new UrlHelper($this->createMock(RouterInterface::class));
        $middleware = new UrlHelperMiddleware($helper);

        $routeResult = RouteResult::fromRouteFailure([]);
        self::assertSame($response, $middleware->process(
            (new ServerRequest())->withAttribute(RouteResult::class, $routeResult),
            $handler,
        ));

        self::assertSame($routeResult, $helper->getRouteResult());

        self::assertSame($response, $middleware->process(
            new ServerRequest(),
            $handler,
        ));

        self::assertNull($helper->getRouteResult());
    }
}
