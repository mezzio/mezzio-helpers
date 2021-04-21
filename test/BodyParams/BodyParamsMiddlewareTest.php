<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Helper\BodyParams;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\Helper\BodyParams\StrategyInterface;
use Mezzio\Helper\Exception\MalformedRequestBodyException;
use MezzioTest\Helper\AttributeAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function fopen;
use function fwrite;
use function get_class;
use function json_encode;

class BodyParamsMiddlewareTest extends TestCase
{
    use AttributeAssertionsTrait;
    use ProphecyTrait;

    /** @var Stream */
    private $body;

    /** @var BodyParamsMiddleware */
    private $bodyParams;

    public function setUp(): void
    {
        $this->bodyParams = new BodyParamsMiddleware();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, json_encode(['foo' => 'bar']));

        $this->body = new Stream($stream);
        $this->body->rewind();
    }

    /** @return RequestHandlerInterface|ObjectProphecy */
    private function mockHandler(callable $callback)
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);

        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->will(function ($args) use ($callback) {
                $request = $args[0];
                return $callback($request);
            });

        return $handler;
    }

    /** @return RequestHandlerInterface|ObjectProphecy */
    private function mockHandlerToNeverTrigger()
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);

        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled();

        return $handler;
    }

    /** @return array<array-key, string[]> */
    public function jsonProvider(): array
    {
        return [
            ['application/json'],
            ['application/hal+json'],
            ['application/vnd.resource.v2+json'],
        ];
    }

    /**
     * @dataProvider jsonProvider
     */
    public function testParsesRawBodyAndPreservesRawBodyInRequestAttribute(string $contentType): void
    {
        $serverRequest = new ServerRequest([], [], '', 'PUT', $this->body, ['Content-type' => $contentType]);

        $this->bodyParams->process(
            $serverRequest,
            $this->mockHandler(function (ServerRequestInterface $request) use (&$serverRequest) {
                $serverRequest = $request;
                return new Response();
            })->reveal()
        );

        $this->assertSame(
            json_encode(['foo' => 'bar']),
            $serverRequest->getAttribute('rawBody')
        );
        $this->assertSame(['foo' => 'bar'], $serverRequest->getParsedBody());
    }

    /** @return array<array-key, string[]> */
    public function notApplicableProvider(): array
    {
        return [
            ['GET', 'application/json'],
            ['HEAD', 'application/json'],
            ['OPTIONS', 'application/json'],
            ['GET', 'application/x-www-form-urlencoded'],
            ['DELETE', 'this-isnt-a-real-content-type'],
        ];
    }

    /**
     * @dataProvider notApplicableProvider
     */
    public function testRequestIsUnchangedWhenBodyParamsMiddlewareIsNotApplicable(
        string $method,
        string $contentType
    ): void {
        $originalRequest = new ServerRequest([], [], '', $method, $this->body, ['Content-type' => $contentType]);
        $finalRequest    = null;

        $this->bodyParams->process(
            $originalRequest,
            $this->mockHandler(function (ServerRequestInterface $request) use (&$finalRequest) {
                $finalRequest = $request;
                return new Response();
            })->reveal()
        );

        $this->assertSame($originalRequest, $finalRequest);
    }

    public function testCanClearStrategies(): void
    {
        $this->bodyParams->clearStrategies();
        $this->assertAttributeSame([], 'strategies', $this->bodyParams);
    }

    public function testCanAttachCustomStrategies(): void
    {
        $strategy = $this->prophesize(StrategyInterface::class)->reveal();
        $this->bodyParams->addStrategy($strategy);
        $this->assertAttributeContains($strategy, 'strategies', $this->bodyParams);
    }

    public function testCustomStrategiesCanMatchRequests(): void
    {
        $middleware       = $this->bodyParams;
        $serverRequest    = new ServerRequest([], [], '', 'PUT', $this->body, ['Content-type' => 'foo/bar']);
        $expectedReturn   = $this->prophesize(ServerRequestInterface::class)->reveal();
        $expectedResponse = new Response();
        $strategy         = $this->prophesize(StrategyInterface::class);
        $strategy->match('foo/bar')->willReturn(true);
        $strategy->parse($serverRequest)->willReturn($expectedReturn);
        $middleware->addStrategy($strategy->reveal());

        $response = $middleware->process(
            $serverRequest,
            $this->mockHandler(function (ServerRequestInterface $request) use ($expectedReturn, $expectedResponse) {
                $this->assertSame($expectedReturn, $request);
                return $expectedResponse;
            })->reveal()
        );

        $this->assertSame($expectedResponse, $response);
    }

    public function testCallsNextWithOriginalRequestWhenNoStrategiesMatch(): void
    {
        $middleware = $this->bodyParams;
        $middleware->clearStrategies();
        $serverRequest    = new ServerRequest([], [], '', 'PUT', $this->body, ['Content-type' => 'foo/bar']);
        $expectedResponse = new Response();

        $response = $middleware->process(
            $serverRequest,
            $this->mockHandler(function (ServerRequestInterface $request) use ($serverRequest, $expectedResponse) {
                $this->assertSame($serverRequest, $request);
                return $expectedResponse;
            })->reveal()
        );

        $this->assertSame($expectedResponse, $response);
    }

    public function testThrowsMalformedRequestBodyExceptionWhenRequestBodyIsNotValidJson(): void
    {
        $expectedException = new MalformedRequestBodyException('malformed request body');

        $middleware    = $this->bodyParams;
        $serverRequest = new ServerRequest([], [], '', 'PUT', $this->body, ['Content-type' => 'foo/bar']);
        $strategy      = $this->prophesize(StrategyInterface::class);
        $strategy->match('foo/bar')->willReturn(true);
        $strategy->parse($serverRequest)->willThrow($expectedException);
        $middleware->addStrategy($strategy->reveal());

        $this->expectException(get_class($expectedException));
        $this->expectExceptionMessage($expectedException->getMessage());
        $this->expectExceptionCode($expectedException->getCode());

        $middleware->process(
            $serverRequest,
            $this->mockHandlerToNeverTrigger()->reveal()
        );
    }

    /** @return array<string, string[]> */
    public function jsonBodyRequests(): array
    {
        return [
            'POST'   => ['POST'],
            'PUT'    => ['PUT'],
            'PATCH'  => ['PATCH'],
            'DELETE' => ['DELETE'],
        ];
    }

    /**
     * @dataProvider jsonBodyRequests
     */
    public function testParsesJsonBodyWhenExpected(string $method): void
    {
        $stream = fopen('php://memory', 'wb+');
        fwrite($stream, json_encode(['foo' => 'bar']));
        $body = new Stream($stream);

        $serverRequest = new ServerRequest(
            [],
            [],
            '',
            $method,
            $body,
            ['Content-type' => 'application/json;charset=utf-8']
        );

        $handlerTriggered = false;

        $result = $this->bodyParams->process(
            $serverRequest,
            $this->mockHandler(function (ServerRequestInterface $request) use ($serverRequest, &$handlerTriggered) {
                $handlerTriggered = true;

                $this->assertNotSame(
                    $request,
                    $serverRequest,
                    'Request passed to handler is the same as the one passed to BodyParamsMiddleware and should not be'
                );

                $this->assertSame(
                    json_encode(['foo' => 'bar']),
                    $request->getAttribute('rawBody'),
                    'Request passed to handler does not contain expected rawBody contents'
                );

                $this->assertSame(
                    ['foo' => 'bar'],
                    $request->getParsedBody(),
                    'Request passed to handler does not contain expected parsed body'
                );

                return new Response();
            })->reveal()
        );

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($handlerTriggered);
    }
}
