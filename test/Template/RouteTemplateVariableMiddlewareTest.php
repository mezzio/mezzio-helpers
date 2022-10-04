<?php

declare(strict_types=1);

namespace MezzioTest\Helper\Template;

use Mezzio\Helper\Template\RouteTemplateVariableMiddleware;
use Mezzio\Helper\Template\TemplateVariableContainer;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Helper\Template\RouteTemplateVariableMiddleware */
final class RouteTemplateVariableMiddlewareTest extends TestCase
{
    /** @var ServerRequestInterface&MockObject */
    private ServerRequestInterface $request;

    /** @var ResponseInterface&MockObject */
    private ResponseInterface $response;

    /** @var RequestHandlerInterface&MockObject */
    private RequestHandlerInterface $handler;

    /** @var TemplateVariableContainer&MockObject */
    private TemplateVariableContainer $container;

    private RouteTemplateVariableMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request   = $this->createMock(ServerRequestInterface::class);
        $this->response  = $this->createMock(ResponseInterface::class);
        $this->handler   = $this->createMock(RequestHandlerInterface::class);
        $this->container = $this->createMock(TemplateVariableContainer::class);

        $this->middleware = new RouteTemplateVariableMiddleware();
    }

    public function testMiddlewareInjectsVariableContainerWithNullRouteIfNoVariableContainerOrRouteResultPresent(): void
    {
        $routeResult       = null;
        $fallbackContainer = new TemplateVariableContainer();

        $this->request
            ->expects(self::exactly(2))
            ->method('getAttribute')
            ->withConsecutive(
                [TemplateVariableContainer::class, $fallbackContainer],
                [RouteResult::class, null],
            )
            ->willReturn(
                $fallbackContainer,
                $routeResult
            );

        $this->request
            ->expects(self::once())
            ->method('withAttribute')
            ->with(
                TemplateVariableContainer::class,
                self::callback(static function (TemplateVariableContainer $container) use ($routeResult): bool {
                    self::assertTrue($container->has('route'));
                    self::assertSame($routeResult, $container->get('route'));
                    self::assertSame(1, $container->count());

                    return true;
                }),
            )
            ->willReturn($this->request);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        self::assertSame(
            $this->response,
            $this->middleware->process($this->request, $this->handler)
        );
    }

    public function testMiddlewareWillInjectNullValueForRouteIfNoRouteResultInRequest(): void
    {
        $routeResult = null;

        $this->container
            ->expects(self::once())
            ->method('with')
            ->with('route', $routeResult)
            ->willReturnSelf();

        $this->request
            ->expects(self::exactly(2))
            ->method('getAttribute')
            ->withConsecutive(
                [TemplateVariableContainer::class, new TemplateVariableContainer()],
                [RouteResult::class, null],
            )
            ->willReturn($this->container, $routeResult);

        $this->request
            ->expects(self::once())
            ->method('withAttribute')
            ->with(
                TemplateVariableContainer::class,
                $this->container
            )
            ->willReturn($this->request);

        $this->request
            ->expects(self::once())
            ->method('withAttribute')
            ->with(
                TemplateVariableContainer::class,
                $this->container
            )
            ->willReturn($this->request);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        self::assertSame(
            $this->response,
            $this->middleware->process($this->request, $this->handler)
        );
    }

    public function testMiddlewareWillInjectRoutePulledFromRequestRouteResult(): void
    {
        $routeResult = $this->createMock(RouteResult::class);

        $this->container
            ->expects(self::once())
            ->method('with')
            ->with('route', $routeResult)
            ->willReturnSelf();

        $this->request
            ->expects(self::exactly(2))
            ->method('getAttribute')
            ->withConsecutive(
                [TemplateVariableContainer::class, new TemplateVariableContainer()],
                [RouteResult::class, null],
            )
            ->willReturn($this->container, $routeResult);

        $this->request
            ->expects(self::once())
            ->method('withAttribute')
            ->with(
                TemplateVariableContainer::class,
                $this->container
            )
            ->willReturn($this->request);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        self::assertSame(
            $this->response,
            $this->middleware->process($this->request, $this->handler)
        );
    }
}
