<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Helper\Template;

use Mezzio\Helper\Template\TemplateVariableContainer;
use Mezzio\Helper\Template\TemplateVariableContainerMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TemplateVariableContainerMiddlewareTest extends TestCase
{
    public function setUp()
    {
        $this->handler    = $this->prophesize(RequestHandlerInterface::class);
        $this->request    = $this->prophesize(ServerRequestInterface::class);
        $this->response   = $this->prophesize(ResponseInterface::class)->reveal();
        $this->middleware = new TemplateVariableContainerMiddleware();
    }

    public function testProcessInjectsVariableContainerIntoRequestPassedToHandler()
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
            ->withAttribute(\Zend\Expressive\Helper\Template\TemplateVariableContainer::class, Argument::type(TemplateVariableContainer::class))
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

    public function testProcessIsANoOpIfVariableContainerIsAlreadyInRequest()
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
            ->withAttribute(\Zend\Expressive\Helper\Template\TemplateVariableContainer::class, $container)
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
