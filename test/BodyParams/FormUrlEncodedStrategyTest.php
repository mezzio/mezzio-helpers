<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Helper\BodyParams;

use Mezzio\Helper\BodyParams\FormUrlEncodedStrategy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class FormUrlEncodedStrategyTest extends TestCase
{
    use ProphecyTrait;

    /** @var FormUrlEncodedStrategy */
    private $strategy;

    public function setUp(): void
    {
        $this->strategy = new FormUrlEncodedStrategy();
    }

    public function formContentTypes()
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
     * @param string $contentType
     */
    public function testMatchesFormUrlencodedTypes($contentType)
    {
        $this->assertTrue($this->strategy->match($contentType));
    }

    public function invalidContentTypes()
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
     * @param string $contentType
     */
    public function testDoesNotMatchNonFormUrlencodedTypes($contentType)
    {
        $this->assertFalse($this->strategy->match($contentType));
    }

    public function testParseReturnsOriginalRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['test' => 'value']);

        $this->assertSame($request->reveal(), $this->strategy->parse($request->reveal()));
    }

    public function testParseReturnsOriginalRequestIfBodyIsEmpty()
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->__toString()->willReturn('');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(null);
        $request->getBody()->willReturn($stream);

        $this->assertSame($request->reveal(), $this->strategy->parse($request->reveal()));
    }

    public function testParseReturnsNewRequest()
    {
        $body = 'foo=bar&bar=foo';

        $stream = $this->prophesize(StreamInterface::class);
        $stream->__toString()->shouldBeCalled()->willReturn($body);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(null);
        $request->getBody()->willReturn($stream->reveal());
        $request->withParsedBody(['foo' => 'bar', 'bar' => 'foo'])->shouldBeCalled()->willReturn($request->reveal());
        $this->assertSame($request->reveal(), $this->strategy->parse($request->reveal()));
    }
}
