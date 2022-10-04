<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use Laminas\Diactoros\Uri;
use Mezzio\Helper\ServerUrlHelper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/** @covers \Mezzio\Helper\ServerUrlHelper */
final class ServerUrlHelperTest extends TestCase
{
    private ServerUrlHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = new ServerUrlHelper();
    }

    /**
     * @psalm-return array<string, array{
     *     0: null|string,
     *     1: string
     * }>
     */
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
        self::assertSame($expected, $this->helper->__invoke($path));
    }

    /**
     * @psalm-return array<string, array{
     *     0: Uri,
     *     1: null|string,
     *     2: string
     * }>
     */
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
        $this->helper->setUri($uri);

        self::assertSame($expected, $this->helper->__invoke($path));
    }

    /**
     * @psalm-return array<string, array{
     *     0: Uri,
     *     1: null|string,
     *     2: string
     * }>
     */
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
        $this->helper->setUri($uri);

        self::assertSame($expected, $this->helper->__invoke($path));
    }

    /**
     * @psalm-return array<string, array{
     *     0: Uri,
     *     1: null|string,
     *     2: string
     * }>
     */
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
        $this->helper->setUri($uri);

        self::assertSame($expected, $this->helper->__invoke($path));
    }

    /**
     * @psalm-return array<string, array{
     *     0: Uri,
     *     1: string,
     *     2: string
     * }>
     */
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
        $this->helper->setUri($uri);

        self::assertSame($expected, $this->helper->__invoke($path));
    }

    /**
     * @psalm-return array<string, array{
     *     0: Uri,
     *     1: string,
     *     2: string
     * }>
     */
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
        $this->helper->setUri($uri);

        self::assertSame($expected, $this->helper->__invoke($path));
    }

    public function testGenerateProxiesToInvokeMethod(): void
    {
        $path = '/foo';

        $helper = new class extends ServerUrlHelper
        {
            public bool $invoked = false;

            /** {@inheritDoc} */
            public function __invoke(?string $path = null): string
            {
                $this->invoked = true;

                return 'it worked';
            }

            /** {@inheritDoc} */
            public function generate(?string $path = null): string
            {
                return parent::generate($path);
            }
        };

        self::assertFalse($helper->invoked);

        $generatedPath = $helper->generate($path);

        self::assertTrue($helper->invoked);
        self::assertSame('it worked', $generatedPath);
        self::assertSame('it worked', $helper->__invoke($path));
    }
}
