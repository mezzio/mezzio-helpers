<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Laminas\Diactoros\Uri;
use Mezzio\Helper\ServerUrlHelper;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class ServerUrlHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @return array<string, string|null[]> */
    public function plainPaths(): array
    {
        return [
            'null'          => [null,       '/'],
            'empty'         => ['',         '/'],
            'root'          => ['/',        '/'],
            'relative-path' => ['foo/bar',  '/foo/bar'],
            'abs-path'      => ['/foo/bar', '/foo/bar'],
        ];
    }

    /**
     * @dataProvider plainPaths
     */
    public function testInvocationReturnsPathOnlyIfNoUriInjected(?string $path, string $expected): void
    {
        $helper = new ServerUrlHelper();
        $this->assertEquals($expected, $helper($path));
    }

    /** @return array<string, Uri|string|null[]> */
    public function plainPathsForUseWithUri(): array
    {
        $uri = new Uri('https://example.com/resource');
        return [
            'null'          => [$uri, null,       'https://example.com/resource'],
            'empty'         => [$uri, '',         'https://example.com/resource'],
            'root'          => [$uri, '/',        'https://example.com/'],
            'relative-path' => [$uri, 'foo/bar',  'https://example.com/resource/foo/bar'],
            'abs-path'      => [$uri, '/foo/bar', 'https://example.com/foo/bar'],
        ];
    }

    /**
     * @dataProvider plainPathsForUseWithUri
     */
    public function testInvocationReturnsUriComposingPathWhenUriInjected(
        UriInterface $uri,
        ?string $path,
        string $expected
    ): void {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals((string) $expected, $helper($path));
    }

    /** @return array<string, Uri|string|null[]> */
    public function uriWithQueryString(): array
    {
        $uri = new Uri('https://example.com/resource?bar=baz');
        return [
            'null'          => [$uri, null,       'https://example.com/resource'],
            'empty'         => [$uri, '',         'https://example.com/resource'],
            'root'          => [$uri, '/',        'https://example.com/'],
            'relative-path' => [$uri, 'foo/bar',  'https://example.com/resource/foo/bar'],
            'abs-path'      => [$uri, '/foo/bar', 'https://example.com/foo/bar'],
        ];
    }

    /**
     * @dataProvider uriWithQueryString
     */
    public function testStripsQueryStringFromInjectedUri(UriInterface $uri, ?string $path, string $expected): void
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }

    /** @return array<string, Uri|string|null[]> */
    public function uriWithFragment(): array
    {
        $uri = new Uri('https://example.com/resource#bar');
        return [
            'null'          => [$uri, null,       'https://example.com/resource'],
            'empty'         => [$uri, '',         'https://example.com/resource'],
            'root'          => [$uri, '/',        'https://example.com/'],
            'relative-path' => [$uri, 'foo/bar',  'https://example.com/resource/foo/bar'],
            'abs-path'      => [$uri, '/foo/bar', 'https://example.com/foo/bar'],
        ];
    }

    /**
     * @dataProvider uriWithFragment
     */
    public function testStripsFragmentFromInjectedUri(UriInterface $uri, ?string $path, string $expected): void
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }

    /** @return array<string, Uri|string|null[]> */
    public function pathsWithQueryString(): array
    {
        $uri = new Uri('https://example.com/resource');
        return [
            'empty-path'    => [$uri, '?foo=bar',         'https://example.com/resource?foo=bar'],
            'root-path'     => [$uri, '/?foo=bar',        'https://example.com/?foo=bar'],
            'relative-path' => [$uri, 'foo/bar?foo=bar',  'https://example.com/resource/foo/bar?foo=bar'],
            'abs-path'      => [$uri, '/foo/bar?foo=bar', 'https://example.com/foo/bar?foo=bar'],
        ];
    }

    /**
     * @dataProvider pathsWithQueryString
     */
    public function testUsesQueryStringFromProvidedPath(UriInterface $uri, ?string $path, string $expected): void
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }

    /** @return array<string, Uri|string[]> */
    public function pathsWithFragment(): array
    {
        $uri = new Uri('https://example.com/resource');
        return [
            'empty-path'    => [$uri, '#bar',         'https://example.com/resource#bar'],
            'root-path'     => [$uri, '/#bar',        'https://example.com/#bar'],
            'relative-path' => [$uri, 'foo/bar#bar',  'https://example.com/resource/foo/bar#bar'],
            'abs-path'      => [$uri, '/foo/bar#bar', 'https://example.com/foo/bar#bar'],
        ];
    }

    /**
     * @dataProvider pathsWithFragment
     */
    public function testUsesFragmentFromProvidedPath(UriInterface $uri, ?string $path, string $expected): void
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }

    public function testGenerateProxiesToInvokeMethod(): void
    {
        $path = '/foo';

        $helper = Mockery::mock(ServerUrlHelper::class)->shouldDeferMissing();
        $helper->shouldReceive('__invoke')
            ->once()
            ->with($path)
            ->andReturn('it worked');

        $this->assertSame('it worked', $helper->generate($path));
    }
}
