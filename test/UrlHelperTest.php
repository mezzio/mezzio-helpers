<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use InvalidArgumentException;
use Mezzio\Helper\Exception\RuntimeException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Exception\RuntimeException as RouterException;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use stdClass;
use TypeError;

#[CoversClass(UrlHelper::class)]
final class UrlHelperTest extends TestCase
{
    use AttributeAssertionsTrait;

    /** @var RouterInterface&MockObject */
    private RouterInterface $router;
    private UrlHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->createMock(RouterInterface::class);
        $this->helper = new UrlHelper($this->router);
    }

    private function createRequest(?RouteResult $routeResult): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::any())
            ->method('getAttribute')
            ->with($this->identicalTo(RouteResult::class))
            ->willReturn($routeResult);

        $request
            ->expects(self::any())
            ->method('withAttribute')
            ->willReturnCallback(function (string $name, $value): ServerRequestInterface {
                self::assertSame(RouteResult::class, $name);
                self::assertInstanceOf(RouteResult::class, $value);

                return $this->createRequest($value);
            });

        return $request;
    }

    public function testRaisesExceptionOnInvocationIfNoRouteProvidedAndNoResultPresent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('use matched result');

        ($this->helper)();
    }

    /**
     * @param non-empty-string $path
     * @param array<string, mixed> $matchedParams
     */
    private function generateRouteResult(
        bool $failure,
        string $path = '/foo',
        string|null $name = null,
        array $matchedParams = [],
    ): RouteResult {
        if ($failure) {
            return RouteResult::fromRouteFailure(null);
        }

        return RouteResult::fromRoute(new Route(
            $path,
            $this->createMock(MiddlewareInterface::class),
            null,
            $name,
        ), $matchedParams);
    }

    public function testRaisesExceptionOnInvocationIfNoRouteProvidedAndResultIndicatesFailure(): void
    {
        $result = $this->generateRouteResult(true);
        $this->helper->setRequest($this->createRequest($result));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('routing failed');

        ($this->helper)();
    }

    public function testRaisesExceptionOnInvocationIfRouterCannotGenerateUriForRouteProvided(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', [], [])
            ->willThrowException(new RouterException());

        $this->expectException(RouterException::class);

        ($this->helper)('foo');
    }

    public function testWhenNoRouteProvidedTheHelperUsesComposedResultToGenerateUrl(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'foo', ['bar' => 'baz']);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('URL');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)());
    }

    public function testWhenNoRouteProvidedTheHelperMergesPassedParametersWithResultParametersToGenerateUrl(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'foo', ['bar' => 'baz']);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz', 'baz' => 'bat'], [])
            ->willReturn('URL');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)(null, ['baz' => 'bat']));
    }

    public function testWhenRouteProvidedTheHelperDelegatesToTheRouterToGenerateUrl(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('URL');

        self::assertSame('URL', ($this->helper)('foo', ['bar' => 'baz']));
    }

    public function testIfRouteResultRouteNameDoesNotMatchRequestedNameItWillNotMergeParamsToGenerateUri(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'not-resource', ['some' => 'params']);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)('resource'));
    }

    public function testMergesRouteResultParamsWithProvidedParametersToGenerateUri(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource', ['id' => 1]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', ['id' => 1, 'version' => 2], [])
            ->willReturn('URL');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)('resource', ['version' => 2]));
    }

    public function testProvidedParametersOverrideAnyPresentInARouteResultWhenGeneratingUri(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource', ['id' => 1]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', ['id' => 2], [])
            ->willReturn('URL');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)('resource', ['id' => 2]));
    }

    public function testWillNotReuseRouteResultParamsIfReuseResultParamsFlagIsFalseWhenGeneratingUri(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource', ['id' => 1]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)('resource', [], [], null, ['reuse_result_params' => false]));
    }

    public function testCanInjectRouteResult(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource', ['id' => 1]);

        $this->helper->setRequest($this->createRequest($result));

        self::assertAttributeSame($result, 'result', $this->helper);
    }

    public function testAllowsSettingBasePath(): void
    {
        $this->helper->setBasePath('/foo');

        self::assertAttributeEquals('/foo', 'basePath', $this->helper);
    }

    public function testSlashIsPrependedWhenBasePathDoesNotHaveOne(): void
    {
        $this->helper->setBasePath('foo');

        self::assertAttributeEquals('/foo', 'basePath', $this->helper);
    }

    public function testBasePathIsPrependedToGeneratedPath(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('/foo/baz');

        $this->helper->setBasePath('/prefix');

        self::assertSame('/prefix/foo/baz', ($this->helper)('foo', ['bar' => 'baz']));
    }

    public function testBasePathIsPrependedToGeneratedPathWhenUsingRouteResult(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'foo', ['bar' => 'baz']);

        $this->router
            ->expects(self::exactly(2))
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('/foo/baz');

        $this->helper->setBasePath('/prefix');
        $this->helper->setRequest($this->createRequest($result));

        // test with explicit params
        self::assertSame('/prefix/foo/baz', ($this->helper)(null, ['bar' => 'baz']));

        // test with implicit route result params
        self::assertSame('/prefix/foo/baz', ($this->helper)());
    }

    public function testGenerateAndInvokeMethodProduceTheSameResult(): void
    {
        $routeName          = 'foo';
        $routeParams        = ['route' => 'bar'];
        $queryParams        = ['foo' => 'bar'];
        $fragmentIdentifier = 'foobar';
        $options            = ['router' => ['foobar' => 'baz'], 'reuse_result_params' => false];

        self::assertSame(
            $this->helper->__invoke($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options),
            $this->helper->generate($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options),
        );
    }

    /** @return array<array-key, mixed[]> */
    public static function invalidBasePathProvider(): array
    {
        return [
            [new stdClass()],
            [['bar']],
        ];
    }

    #[DataProvider('invalidBasePathProvider')]
    public function testThrowsExceptionWhenSettingInvalidBasePaths(mixed $basePath): void
    {
        $this->expectException(TypeError::class);

        /** @psalm-suppress MixedArgument */
        $this->helper->setBasePath($basePath);
    }

    public function testIfRouteResultIsFailureItWillNotMergeParamsToGenerateUri(): void
    {
        $result = $this->generateRouteResult(true);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)('resource'));
    }

    public function testOptionsArePassedToRouter(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', [], ['bar' => 'baz'])
            ->willReturn('URL');

        self::assertSame('URL', ($this->helper)('foo', [], [], null, ['router' => ['bar' => 'baz']]));
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string|null, 2: string}> */
    public static function queryParametersAndFragmentProvider(): array
    {
        return [
            'none'           => [[], null, ''],
            'query'          => [['qux' => 'quux'], null, '?qux=quux'],
            'fragment'       => [[], 'corge', '#corge'],
            'query+fragment' => [['qux' => 'quux'], 'cor-ge', '?qux=quux#cor-ge'],
        ];
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    #[DataProvider('queryParametersAndFragmentProvider')]
    public function testQueryParametersAndFragment(
        array $queryParams,
        ?string $fragmentIdentifier,
        string $expected,
    ): void {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('/foo/baz');

        self::assertSame(
            '/foo/baz' . $expected,
            ($this->helper)('foo', ['bar' => 'baz'], $queryParams, $fragmentIdentifier),
        );
    }

    /** @return array<array-key, string[]> */
    public static function invalidFragmentProvider(): array
    {
        return [
            [''],
            ['#'],
        ];
    }

    #[DataProvider('invalidFragmentProvider')]
    public function testRejectsInvalidFragmentIdentifier(string $fragmentIdentifier): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', [], [])
            ->willReturn('/foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fragment identifier must conform to RFC 3986');
        $this->expectExceptionCode(400);

        ($this->helper)('foo', [], [], $fragmentIdentifier);
    }

    /**
     * Test written when discovering that generate() uses '' as the default fragment,
     * which __invoke() considers invalid.
     */
    public function testCallingGenerateWithoutFragmentArgumentPassesNullValueForFragment(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', [], [])
            ->willReturn('/foo');

        self::assertSame('/foo', ($this->helper)->generate('foo'));
    }

    #[Group('42')]
    public function testAppendsQueryStringAndFragmentWhenPresentAndRouteNameIsNotProvided(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'matched-route', ['foo' => 'bar']);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('matched-route', ['foo' => 'baz'], [])
            ->willReturn('scheme://host/path');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame(
            'scheme://host/path?query=params&are=present#fragment/exists',
            ($this->helper)(
                null,
                ['foo' => 'baz'],
                ['query' => 'params', 'are' => 'present'],
                'fragment/exists',
            ),
        );
    }

    public function testGetRouteResultIfNoRouteResultSet(): void
    {
        self::assertNull($this->helper->getRouteResult());
    }

    public function testGetRouteResultWithRouteResultSet(): void
    {
        $result = $this->generateRouteResult(false);

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame($result, $this->helper->getRouteResult());
    }

    public function testWillNotReuseQueryParamsIfReuseQueryParamsFlagIsFalseWhenGeneratingUri(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource');

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::never())
            ->method('getQueryParams');

        $this->helper->setRequest($this->createRequest($result));

        self::assertSame('URL', ($this->helper)('resource', [], [], null, ['reuse_query_params' => false]));
    }

    public function testWillReuseQueryParamsIfReuseQueryParamsFlagIsTrueWhenGeneratingUri(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource');

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createRequest($result);

        $request
            ->expects(self::once())
            ->method('getQueryParams')
            ->wilLReturn(['foo' => 'bar']);

        $this->helper->setRequest($request);

        self::assertSame('URL?foo=bar', ($this->helper)('resource', [], [], null, ['reuse_query_params' => true]));
    }

    public function testWillNotReuseQueryParamsIfReuseQueryParamsFlagIsMissingGeneratingUri(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource');

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createRequest($result);

        $request
            ->expects(self::never())
            ->method('getQueryParams');

        $this->helper->setRequest($request);

        self::assertSame('URL', ($this->helper)('resource'));
    }

    public function testCanOverrideRequestQueryParams(): void
    {
        $result = $this->generateRouteResult(false, '/foo', 'resource');

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createRequest($result);

        $request
            ->expects(self::never())
            ->method('getQueryParams');

        $this->helper->setRequest($request);

        self::assertSame('URL?foo=foo', ($this->helper)('resource', [], ['foo' => 'foo']));
    }
}
