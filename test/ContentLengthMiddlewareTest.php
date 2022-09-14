<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\ContentLengthMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function str_repeat;

class ContentLengthMiddlewareTest extends TestCase
{
    private ContentLengthMiddleware $middleware;
    private ServerRequest $serverRequest;

    protected function setUp(): void
    {
        $this->middleware    = new ContentLengthMiddleware();
        $this->serverRequest = new ServerRequest();
    }

    private function handlerWillReturnResponse(ResponseInterface $response): RequestHandlerInterface
    {
        return new class ($response) implements RequestHandlerInterface {
            /** @var ResponseInterface */
            public $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    public function testReturnsResponseVerbatimIfContentLengthHeaderPresent(): void
    {
        $response = (new HtmlResponse('foo'))->withAddedHeader('Content-Length', '3');
        $handler  = $this->handlerWillReturnResponse($response);
        $this->assertSame($response, $this->middleware->process($this->serverRequest, $handler));
    }

    public function testReturnsResponseVerbatimIfContentLengthHeaderNotPresentAndBodySizeIsNull(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())
            ->method('getSize')
            ->willReturn(null);
        $response = new HtmlResponse($stream);
        $handler  = $this->handlerWillReturnResponse($response);
        $this->assertSame($response, $this->middleware->process($this->serverRequest, $handler));
    }

    public function testReturnsResponseWithContentLengthHeaderBasedOnBodySize(): void
    {
        $response = new HtmlResponse(str_repeat('a', 42));
        self::assertFalse($response->hasHeader('Content-Length'));
        $handler  = $this->handlerWillReturnResponse($response);
        $modified = $this->middleware->process($this->serverRequest, $handler);
        self::assertNotSame($response, $modified);
        self::assertTrue($modified->hasHeader('Content-Length'));
        self::assertEquals(42, $modified->getHeader('Content-Length')[0]);
    }
}
