<?php

declare(strict_types=1);

namespace MezzioTest\Helper;

use InvalidArgumentException;
use Mezzio\Helper\Exception\RuntimeException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Exception\RuntimeException as RouterException;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use TypeError;

#[CoversClass(UrlHelper::class)]
final class UrlHelperTest extends TestCase
{
    use AttributeAssertionsTrait;

    /** @var RouterInterface&MockObject */
    private RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->createMock(RouterInterface::class);
    }

    public function createHelper(): UrlHelper
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::never())
            ->method('getQueryParams')
            ->willReturn([]);

        $helper = new UrlHelper($this->router);
        $helper->setRequest($request);

        return $helper;
    }

    public function testRaisesExceptionOnInvocationIfNoRouteProvidedAndNoResultPresent(): void
    {
        $helper = $this->createHelper();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('use matched result');

        $helper();
    }

    public function testReturnBasePathOnInvocationIfNoRouteProvidedAndResultIndicatesFailure(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result
            ->expects(self::atLeastOnce())
            ->method('isFailure')
            ->willReturn(true);

        $helper = $this->createHelper();
        $helper->setRouteResult($result);
        $helper->setBasePath('/foo');

        self::assertSame('/foo', $helper());
    }

    public function testRaisesExceptionOnInvocationIfRouterCannotGenerateUriForRouteProvided(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', [], [])
            ->willThrowException(new RouterException());

        $helper = $this->createHelper();

        $this->expectException(RouterException::class);

        $helper('foo');
    }

    public function testWhenNoRouteProvidedTheHelperUsesComposedResultToGenerateUrl(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('foo');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn(['bar' => 'baz']);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame('URL', $helper());
    }

    public function testWhenNoRouteProvidedTheHelperMergesPassedParametersWithResultParametersToGenerateUrl(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('foo');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn(['bar' => 'baz']);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz', 'baz' => 'bat'], [])
            ->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame('URL', $helper(null, ['baz' => 'bat']));
    }

    public function testWhenRouteProvidedTheHelperDelegatesToTheRouterToGenerateUrl(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('URL');

        $helper = $this->createHelper();

        self::assertSame('URL', $helper('foo', ['bar' => 'baz']));
    }

    public function testIfRouteResultRouteNameDoesNotMatchRequestedNameItWillNotMergeParamsToGenerateUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('not-resource');

        $result
            ->expects(self::never())
            ->method('getMatchedParams');

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame('URL', $helper('resource'));
    }

    public function testMergesRouteResultParamsWithProvidedParametersToGenerateUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('resource');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn(['id' => 1]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', ['id' => 1, 'version' => 2], [])
            ->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame('URL', $helper('resource', ['version' => 2]));
    }

    public function testProvidedParametersOverrideAnyPresentInARouteResultWhenGeneratingUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('resource');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn(['id' => 1]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', ['id' => 2], [])
            ->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame('URL', $helper('resource', ['id' => 2]));
    }

    public function testWillNotReuseRouteResultParamsIfReuseResultParamsFlagIsFalseWhenGeneratingUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::never())
            ->method('isFailure');

        $result
            ->expects(self::never())
            ->method('getMatchedRouteName');

        $result
            ->expects(self::never())
            ->method('getMatchedParams');

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame('URL', $helper('resource', [], [], null, ['reuse_result_params' => false]));
    }

    public function testCanInjectRouteResult(): void
    {
        $result = $this->createMock(RouteResult::class);

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertAttributeSame($result, 'result', $helper);
    }

    public function testAllowsSettingBasePath(): void
    {
        $helper = $this->createHelper();
        $helper->setBasePath('/foo');

        self::assertAttributeEquals('/foo', 'basePath', $helper);
    }

    public function testSlashIsPrependedWhenBasePathDoesNotHaveOne(): void
    {
        $helper = $this->createHelper();
        $helper->setBasePath('foo');

        self::assertAttributeEquals('/foo', 'basePath', $helper);
    }

    public function testBasePathIsPrependedToGeneratedPath(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('/foo/baz');

        $helper = $this->createHelper();
        $helper->setBasePath('/prefix');

        self::assertSame('/prefix/foo/baz', $helper('foo', ['bar' => 'baz']));
    }

    public function testBasePathIsPrependedToGeneratedPathWhenUsingRouteResult(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::exactly(2))
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::exactly(2))
            ->method('getMatchedRouteName')
            ->willReturn('foo');

        $result
            ->expects(self::exactly(2))
            ->method('getMatchedParams')
            ->willReturn(['bar' => 'baz']);

        $this->router
            ->expects(self::exactly(2))
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('/foo/baz');

        $helper = $this->createHelper();
        $helper->setBasePath('/prefix');
        $helper->setRouteResult($result);

        // test with explicit params
        self::assertSame('/prefix/foo/baz', $helper(null, ['bar' => 'baz']));

        // test with implicit route result params
        self::assertSame('/prefix/foo/baz', $helper());
    }

    public function testGenerateAndInvokeMethodProduceTheSameResult(): void
    {
        $routeName          = 'foo';
        $routeParams        = ['route' => 'bar'];
        $queryParams        = ['foo' => 'bar'];
        $fragmentIdentifier = 'foobar';
        $options            = ['router' => ['foobar' => 'baz'], 'reuse_result_params' => false];

        $helper = $this->createHelper();
        self::assertSame(
            $helper->__invoke($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options),
            $helper->generate($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options),
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

        $helper = $this->createHelper();
        /** @psalm-suppress MixedArgument */
        $helper->setBasePath($basePath);
    }

    public function testIfRouteResultIsFailureItWillNotMergeParamsToGenerateUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(true);

        $result
            ->expects(self::never())
            ->method('getMatchedRouteName');

        $result
            ->expects(self::never())
            ->method('getMatchedParams');

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame('URL', $helper('resource'));
    }

    public function testOptionsArePassedToRouter(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', [], ['bar' => 'baz'])
            ->willReturn('URL');

        $helper = $this->createHelper();

        self::assertSame('URL', $helper('foo', [], [], null, ['router' => ['bar' => 'baz']]));
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
        string $expected
    ): void {
        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', ['bar' => 'baz'], [])
            ->willReturn('/foo/baz');

        $helper = $this->createHelper();

        self::assertSame(
            '/foo/baz' . $expected,
            $helper('foo', ['bar' => 'baz'], $queryParams, $fragmentIdentifier)
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fragment identifier must conform to RFC 3986');
        $this->expectExceptionCode(400);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('foo', [], [])
            ->willReturn('/foo');

        $helper = $this->createHelper();
        $helper('foo', [], [], $fragmentIdentifier);
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

        $helper = $this->createHelper();

        self::assertSame('/foo', $helper->generate('foo'));
    }

    #[Group('42')]
    public function testAppendsQueryStringAndFragmentWhenPresentAndRouteNameIsNotProvided(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('matched-route');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn(['foo' => 'bar']);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('matched-route', ['foo' => 'baz'], [])
            ->willReturn('scheme://host/path');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame(
            'scheme://host/path?query=params&are=present#fragment/exists',
            $helper(
                null,
                ['foo' => 'baz'],
                ['query' => 'params', 'are' => 'present'],
                'fragment/exists'
            )
        );
    }

    public function testGetRouteResultIfNoRouteResultSet(): void
    {
        $helper = $this->createHelper();

        self::assertNull($helper->getRouteResult());
    }

    public function testGetRouteResultWithRouteResultSet(): void
    {
        $result = $this->createMock(RouteResult::class);

        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        self::assertSame($result, $helper->getRouteResult());
    }

    public function testWillNotReuseQueryParamsIfReuseQueryParamsFlagIsFalseWhenGeneratingUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('resource');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn([]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::never())
            ->method('getQueryParams');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);
        $helper->setRequest($request);

        self::assertSame('URL', $helper('resource', [], [], null, ['reuse_query_params' => false]));
    }

    public function testWillReuseQueryParamsIfReuseQueryParamsFlagIsTrueWhenGeneratingUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::exactly(2))
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::exactly(2))
            ->method('getMatchedRouteName')
            ->willReturn('resource');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn([]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::once())
            ->method('getQueryParams')
            ->wilLReturn(['foo' => 'bar']);

        $helper = $this->createHelper();
        $helper->setRouteResult($result);
        $helper->setRequest($request);

        self::assertSame('URL?foo=bar', $helper('resource', [], [], null, ['reuse_query_params' => true]));
    }

    public function testWillNotReuseQueryParamsIfReuseQueryParamsFlagIsMissingGeneratingUri(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('resource');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn([]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::never())
            ->method('getQueryParams');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);
        $helper->setRequest($request);

        self::assertSame('URL', $helper('resource'));
    }

    public function testCanOverrideRequestQueryParams(): void
    {
        $result = $this->createMock(RouteResult::class);

        $result
            ->expects(self::once())
            ->method('isFailure')
            ->willReturn(false);

        $result
            ->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('resource');

        $result
            ->expects(self::once())
            ->method('getMatchedParams')
            ->willReturn([]);

        $this->router
            ->expects(self::once())
            ->method('generateUri')
            ->with('resource', [], [])
            ->willReturn('URL');

        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::never())
            ->method('getQueryParams');

        $helper = $this->createHelper();
        $helper->setRouteResult($result);
        $helper->setRequest($request);

        self::assertSame('URL?foo=foo', $helper('resource', [], ['foo' => 'foo']));
    }
}
