<?php

declare(strict_types=1);

namespace MezzioTest\Helper\Template;

use Mezzio\Helper\Template\TemplateVariableContainer;
use Mezzio\Helper\Template\TemplateVariableContainerMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Helper\Template\TemplateVariableContainerMiddleware */
final class TemplateVariableContainerMiddlewareTest extends TestCase
{
    /** @var ServerRequestInterface&MockObject */
    private ServerRequestInterface $request;

    /** @var ResponseInterface&MockObject */
    private ResponseInterface $response;

    /** @var RequestHandlerInterface&MockObject */
    private RequestHandlerInterface $handler;

    private TemplateVariableContainerMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler  = $this->createMock(RequestHandlerInterface::class);
        $this->request  = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->middleware = new TemplateVariableContainerMiddleware();
    }

    public function testProcessInjectsVariableContainerIntoRequestPassedToHandler(): void
    {
        $this->request
            ->expects(self::once())
            ->method('getAttribute')
            ->with(TemplateVariableContainer::class)
            ->willReturn(null);

        $this->request
            ->expects(self::once())
            ->method('withAttribute')
            ->with(TemplateVariableContainer::class, new TemplateVariableContainer())
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

    public function testProcessIsANoOpIfVariableContainerIsAlreadyInRequest(): void
    {
        $container = $this->createMock(TemplateVariableContainer::class);

        $this->request
            ->expects(self::once())
            ->method('getAttribute')
            ->with(TemplateVariableContainer::class)
            ->willReturn($container);

        $this->request
            ->expects(self::never())
            ->method('withAttribute')
            ->with(TemplateVariableContainer::class, new TemplateVariableContainer());

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
