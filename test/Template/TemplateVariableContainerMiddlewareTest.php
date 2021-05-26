<?php

declare(strict_types=1);

namespace MezzioTest\Helper\Template;

use Mezzio\Helper\Template\TemplateVariableContainer;
use Mezzio\Helper\Template\TemplateVariableContainerMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TemplateVariableContainerMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        $this->handler    = $this->prophesize(RequestHandlerInterface::class);
        $this->request    = $this->prophesize(ServerRequestInterface::class);
        $this->response   = $this->prophesize(ResponseInterface::class)->reveal();
        $this->middleware = new TemplateVariableContainerMiddleware();
    }

    public function testProcessInjectsVariableContainerIntoRequestPassedToHandler(): void
    {
        $this->request
            ->getAttribute(TemplateVariableContainer::class)
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $clonedRequest = $this->prophesize(ServerRequestInterface::class)->reveal();
        $this->request
            ->withAttribute(TemplateVariableContainer::class, Argument::type(TemplateVariableContainer::class))
            ->will([$this->request, 'reveal'])
            ->shouldBeCalledTimes(1);
        $this->request
            ->withAttribute(
                \zend\expressive\helper\template\templatevariablecontainer::class,
                Argument::type(TemplateVariableContainer::class)
            )
            ->willReturn($clonedRequest)
            ->shouldBeCalledTimes(1);

        $this->handler
            ->handle($clonedRequest)
            ->willReturn($this->response)
            ->shouldBeCalledTimes(1);

        $this->assertSame(
            $this->response,
            $this->middleware->process(
                $this->request->reveal(),
                $this->handler->reveal()
            )
        );
    }

    public function testProcessIsANoOpIfVariableContainerIsAlreadyInRequest(): void
    {
        $container = new TemplateVariableContainer();

        $this->request
            ->getAttribute(TemplateVariableContainer::class)
            ->willReturn($container)
            ->shouldBeCalledTimes(1);

        $this->request
            ->withAttribute(TemplateVariableContainer::class, $container)
            ->shouldNotBeCalled();
        $this->request
            ->withAttribute(\zend\expressive\helper\template\templatevariablecontainer::class, $container)
            ->shouldNotBeCalled();

        $this->handler
            ->handle(Argument::that([$this->request, 'reveal']))
            ->willReturn($this->response)
            ->shouldBeCalledTimes(1);

        $this->assertSame(
            $this->response,
            $this->middleware->process(
                $this->request->reveal(),
                $this->handler->reveal()
            )
        );
    }
}
