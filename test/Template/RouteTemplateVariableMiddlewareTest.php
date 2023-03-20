<?php

declare(strict_types=1);

namespace MezzioTest\Helper\Template;

use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\Template\RouteTemplateVariableMiddleware;
use Mezzio\Helper\Template\TemplateVariableContainer;
use Mezzio\Router\RouteResult;
use MezzioTest\Helper\RequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteTemplateVariableMiddleware::class)]
final class RouteTemplateVariableMiddlewareTest extends TestCase
{
    private RouteTemplateVariableMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new RouteTemplateVariableMiddleware();
    }

    public function testThatTheResponseIsUnmodified(): void
    {
        $response = new TextResponse('Foo');
        $handler  = new RequestHandler($response);

        self::assertSame(
            $response,
            $this->middleware->process(new ServerRequest(), $handler),
        );
    }

    public function testThatAVariableContainerWillBeAddedAsARequestAttributeWhenNoneIsPresent(): void
    {
        $request = new ServerRequest();
        self::assertNull($request->getAttribute(TemplateVariableContainer::class));

        $handler = new RequestHandler();
        $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        $received = $handler->received();
        self::assertNotSame($request, $received);
        self::assertInstanceOf(
            TemplateVariableContainer::class,
            $received->getAttribute(TemplateVariableContainer::class),
        );
    }

    public function testThatExistingVariablesInTheContainerWillBePreserved(): void
    {
        $container = (new TemplateVariableContainer())
            ->with('foo', 'bar');

        $request = (new ServerRequest())
            ->withAttribute(TemplateVariableContainer::class, $container);

        $handler = new RequestHandler();
        $this->middleware->process($request, $handler);

        self::assertTrue($handler->didExecute());
        $received = $handler->received();
        self::assertNotSame($request, $received);

        $newContainer = $received->getAttribute(TemplateVariableContainer::class);
        self::assertInstanceOf(TemplateVariableContainer::class, $newContainer);

        self::assertSame('bar', $newContainer->get('foo'));
    }

    public function testThatTheRouteVariableWillBeInjectedWithNullWhenThereIsNoRouteResult(): void
    {
        $handler = new RequestHandler();
        $this->middleware->process(new ServerRequest(), $handler);
        $received  = $handler->received();
        $container = $received->getAttribute(TemplateVariableContainer::class);
        self::assertInstanceOf(TemplateVariableContainer::class, $container);

        self::assertTrue($container->has('route'));
        self::assertNull($container->get('route'));
    }

    public function testThatTheRouteVariableWillContainTheRouteResultInstanceWhenPresent(): void
    {
        $result  = $this->createMock(RouteResult::class);
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $result);

        $handler = new RequestHandler();
        $this->middleware->process($request, $handler);
        $received  = $handler->received();
        $container = $received->getAttribute(TemplateVariableContainer::class);
        self::assertInstanceOf(TemplateVariableContainer::class, $container);

        self::assertTrue($container->has('route'));
        self::assertSame($result, $container->get('route'));
    }
}
