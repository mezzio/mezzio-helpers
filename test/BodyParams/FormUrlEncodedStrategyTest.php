<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Helper\BodyParams;

use Mezzio\Helper\BodyParams\FormUrlEncodedStrategy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class FormUrlEncodedStrategyTest extends TestCase
{
    /** @var FormUrlEncodedStrategy */
    private $strategy;

    public function setUp(): void
    {
        $this->strategy = new FormUrlEncodedStrategy();
    }

    /** @return array<array-key, string[]> */
    public function formContentTypes(): array
    {
        return [
            ['application/x-www-form-urlencoded'],
            ['application/x-www-form-urlencoded; charset=utf-8'],
            ['application/x-www-form-urlencoded;charset=utf-8'],
            ['application/x-www-form-urlencoded;Charset="utf-8"'],
        ];
    }

    /**
     * @dataProvider formContentTypes
     */
    public function testMatchesFormUrlencodedTypes(string $contentType): void
    {
        $this->assertTrue($this->strategy->match($contentType));
    }

    /** @return array<array-key, string[]> */
    public function invalidContentTypes(): array
    {
        return [
            ['application/x-www-form-urlencoded2'],
            ['application/x-www-form-urlencoded-too'],
            ['form/multipart'],
            ['application/json'],
        ];
    }

    /**
     * @dataProvider invalidContentTypes
     */
    public function testDoesNotMatchNonFormUrlencodedTypes(string $contentType): void
    {
        $this->assertFalse($this->strategy->match($contentType));
    }

    public function testParseReturnsOriginalRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())
            ->method('getParsedBody')
            ->willReturn(['test' => 'value']);

        $this->assertSame($request, $this->strategy->parse($request));
    }

    /** @psalm-return ServerRequestInterface&MockObject */
    private function requestWithRawBodyIsNotYetParsed(string $rawBody): ServerRequestInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())
            ->method('__toString')
            ->willReturn($rawBody);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())
            ->method('getBody')
            ->willReturn($stream);

        $request->expects(self::once())
            ->method('getParsedBody')
            ->willReturn(null);

        return $request;
    }

    public function testParseReturnsOriginalRequestIfBodyIsEmpty(): void
    {
        $request = $this->requestWithRawBodyIsNotYetParsed('');

        $this->assertSame($request, $this->strategy->parse($request));
    }

    public function testParseReturnsNewRequest(): void
    {
        $body    = 'foo=bar&bar=foo';
        $expect  = ['foo' => 'bar', 'bar' => 'foo'];
        $request = $this->requestWithRawBodyIsNotYetParsed($body);
        $request->expects(self::once())
            ->method('withParsedBody')
            ->with(self::equalTo($expect))
            ->willReturnSelf();

        $this->assertSame($request, $this->strategy->parse($request));
    }
}
