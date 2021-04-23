<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Helper;

use InvalidArgumentException;
use Mezzio\Helper\Exception\RuntimeException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Exception\RuntimeException as RouterException;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use TypeError;

class UrlHelperTest extends TestCase
{
    use AttributeAssertionsTrait;
    use MockeryPHPUnitIntegration;
    use ProphecyTrait;

    /** @var RouterInterface|ObjectProphecy */
    private $router;

    public function setUp(): void
    {
        $this->router = $this->prophesize(RouterInterface::class);
    }

    public function createHelper(): UrlHelper
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn([]);

        $helper = new UrlHelper($this->router->reveal());
        $helper->setRequest($request->reveal());
        return $helper;
    }

    public function testRaisesExceptionOnInvocationIfNoRouteProvidedAndNoResultPresent(): void
    {
        $helper = $this->createHelper();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('use matched result');
        $helper();
    }

    public function testRaisesExceptionOnInvocationIfNoRouteProvidedAndResultIndicatesFailure(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->expects(self::atLeastOnce())
            ->method('isFailure')
            ->willReturn(true);
        $helper = $this->createHelper();
        $helper->setRouteResult($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('routing failed');
        $helper();
    }

    public function testRaisesExceptionOnInvocationIfRouterCannotGenerateUriForRouteProvided(): void
    {
        $this->router->generateUri('foo', [], [])->willThrow(RouterException::class);
        $helper = $this->createHelper();

        $this->expectException(RouterException::class);
        $helper('foo');
    }

    public function testWhenNoRouteProvidedTheHelperUsesComposedResultToGenerateUrl(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('foo');
        $result->getMatchedParams()->willReturn(['bar' => 'baz']);

        $this->router->generateUri('foo', ['bar' => 'baz'], [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper());
    }

    public function testWhenNoRouteProvidedTheHelperMergesPassedParametersWithResultParametersToGenerateUrl(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('foo');
        $result->getMatchedParams()->willReturn(['bar' => 'baz']);

        $this->router->generateUri('foo', ['bar' => 'baz', 'baz' => 'bat'], [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper(null, ['baz' => 'bat']));
    }

    public function testWhenRouteProvidedTheHelperDelegatesToTheRouterToGenerateUrl(): void
    {
        $this->router->generateUri('foo', ['bar' => 'baz'], [])->willReturn('URL');
        $helper = $this->createHelper();
        $this->assertEquals('URL', $helper('foo', ['bar' => 'baz']));
    }

    public function testIfRouteResultRouteNameDoesNotMatchRequestedNameItWillNotMergeParamsToGenerateUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('not-resource');
        $result->getMatchedParams()->shouldNotBeCalled();

        $this->router->generateUri('resource', [], [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource'));
    }

    public function testMergesRouteResultParamsWithProvidedParametersToGenerateUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn(['id' => 1]);

        $this->router->generateUri('resource', ['id' => 1, 'version' => 2], [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource', ['version' => 2]));
    }

    public function testProvidedParametersOverrideAnyPresentInARouteResultWhenGeneratingUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn(['id' => 1]);

        $this->router->generateUri('resource', ['id' => 2], [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource', ['id' => 2]));
    }

    public function testWillNotReuseRouteResultParamsIfReuseResultParamsFlagIsFalseWhenGeneratingUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn(['id' => 1]);

        $this->router->generateUri('resource', [], [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource', [], [], null, ['reuse_result_params' => false]));
    }

    public function testCanInjectRouteResult(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());
        $this->assertAttributeSame($result->reveal(), 'result', $helper);
    }

    public function testAllowsSettingBasePath(): void
    {
        $helper = $this->createHelper();
        $helper->setBasePath('/foo');
        $this->assertAttributeEquals('/foo', 'basePath', $helper);
    }

    public function testSlashIsPrependedWhenBasePathDoesNotHaveOne(): void
    {
        $helper = $this->createHelper();
        $helper->setBasePath('foo');
        $this->assertAttributeEquals('/foo', 'basePath', $helper);
    }

    public function testBasePathIsPrependedToGeneratedPath(): void
    {
        $this->router->generateUri('foo', ['bar' => 'baz'], [])->willReturn('/foo/baz');
        $helper = $this->createHelper();
        $helper->setBasePath('/prefix');
        $this->assertEquals('/prefix/foo/baz', $helper('foo', ['bar' => 'baz']));
    }

    public function testBasePathIsPrependedToGeneratedPathWhenUsingRouteResult(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('foo');
        $result->getMatchedParams()->willReturn(['bar' => 'baz']);

        $this->router->generateUri('foo', ['bar' => 'baz'], [])->willReturn('/foo/baz');

        $helper = $this->createHelper();
        $helper->setBasePath('/prefix');
        $helper->setRouteResult($result->reveal());

        // test with explicit params
        $this->assertEquals('/prefix/foo/baz', $helper(null, ['bar' => 'baz']));

        // test with implicit route result params
        $this->assertEquals('/prefix/foo/baz', $helper());
    }

    public function testGenerateProxiesToInvokeMethod(): void
    {
        $routeName          = 'foo';
        $routeParams        = ['bar'];
        $queryParams        = ['foo' => 'bar'];
        $fragmentIdentifier = 'foobar';
        $options            = ['router' => ['foobar' => 'baz'], 'reuse_result_params' => false];

        $helper = Mockery::mock(UrlHelper::class)->makePartial();
        $helper->shouldReceive('__invoke')
            ->once()
            ->with($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options)
            ->andReturn('it worked');

        $this->assertSame(
            'it worked',
            $helper->generate($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options)
        );
    }

    /** @return array<array-key, mixed[]> */
    public function invalidBasePathProvider(): array
    {
        return [
            [new stdClass('foo')],
            [['bar']],
        ];
    }

    /**
     * @dataProvider invalidBasePathProvider
     * @param mixed $basePath
     */
    public function testThrowsExceptionWhenSettingInvalidBasePaths($basePath): void
    {
        $this->expectException(TypeError::class);

        $helper = $this->createHelper();
        $helper->setBasePath($basePath);
    }

    public function testIfRouteResultIsFailureItWillNotMergeParamsToGenerateUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(true);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->shouldNotBeCalled();

        $this->router->generateUri('resource', [], [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource'));
    }

    public function testOptionsArePassedToRouter(): void
    {
        $this->router->generateUri('foo', [], ['bar' => 'baz'])->willReturn('URL');
        $helper = $this->createHelper();
        $this->assertEquals('URL', $helper('foo', [], [], null, ['router' => ['bar' => 'baz']]));
    }

    /** @return array<string, mixed[]> */
    public function queryParametersAndFragmentProvider(): array
    {
        return [
            'none'           => [[], null, ''],
            'query'          => [['qux' => 'quux'], null, '?qux=quux'],
            'fragment'       => [[], 'corge', '#corge'],
            'query+fragment' => [['qux' => 'quux'], 'cor-ge', '?qux=quux#cor-ge'],
        ];
    }

    /**
     * @dataProvider queryParametersAndFragmentProvider
     */
    public function testQueryParametersAndFragment(
        array $queryParams,
        ?string $fragmentIdentifier,
        string $expected
    ): void {
        $this->router->generateUri('foo', ['bar' => 'baz'], [])->willReturn('/foo/baz');
        $helper = $this->createHelper();

        $this->assertEquals(
            '/foo/baz' . $expected,
            $helper('foo', ['bar' => 'baz'], $queryParams, $fragmentIdentifier)
        );
    }

    /** @return array<array-key, string[]> */
    public function invalidFragmentProvider(): array
    {
        return [
            [''],
            ['#'],
        ];
    }

    /**
     * @dataProvider invalidFragmentProvider
     */
    public function testRejectsInvalidFragmentIdentifier(string $fragmentIdentifier): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fragment identifier must conform to RFC 3986');
        $this->expectExceptionCode(400);

        $this->router->generateUri('foo', [], [])->willReturn('/foo');

        $helper = $this->createHelper();
        $helper('foo', [], [], $fragmentIdentifier);
    }

    /**
     * Test written when discovering that generate() uses '' as the default fragment,
     * which __invoke() considers invalid.
     */
    public function testCallingGenerateWithoutFragmentArgumentPassesNullValueForFragment(): void
    {
        $this->router->generateUri('foo', [], [])->willReturn('/foo');
        $helper = $this->createHelper();

        $this->assertEquals('/foo', $helper->generate('foo'));
    }

    /**
     * @group 42
     */
    public function testAppendsQueryStringAndFragmentWhenPresentAndRouteNameIsNotProvided(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('matched-route');
        $result->getMatchedParams()->willReturn(['foo' => 'bar']);

        $this->router
            ->generateUri(
                'matched-route',
                ['foo' => 'baz'],
                []
            )
            ->willReturn('scheme://host/path');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals(
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
        $this->assertNull($helper->getRouteResult());
    }

    public function testGetRouteResultWithRouteResultSet(): void
    {
        $helper = $this->createHelper();
        $result = $this->prophesize(RouteResult::class);

        $helper->setRouteResult($result->reveal());
        $this->assertInstanceOf(RouteResult::class, $helper->getRouteResult());
    }

    public function testWillNotReuseQueryParamsIfReuseQueryParamsFlagIsFalseWhenGeneratingUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn([]);

        $this->router->generateUri('resource', [], [])->willReturn('URL');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->wilLReturn(['foo' => 'bar']);

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());
        $helper->setRequest($request->reveal());

        $this->assertEquals('URL', $helper('resource', [], [], null, ['reuse_query_params' => false]));
    }

    public function testWillReuseQueryParamsIfReuseQueryParamsFlagIsTrueWhenGeneratingUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn([]);

        $this->router->generateUri('resource', [], [])->willReturn('URL');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->wilLReturn(['foo' => 'bar']);

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());
        $helper->setRequest($request->reveal());

        $this->assertEquals('URL?foo=bar', $helper('resource', [], [], null, ['reuse_query_params' => true]));
    }

    public function testWillNotReuseQueryParamsIfReuseQueryParamsFlagIsMissingGeneratingUri(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn([]);

        $this->router->generateUri('resource', [], [])->willReturn('URL');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->wilLReturn(['foo' => 'bar']);

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());
        $helper->setRequest($request->reveal());

        $this->assertEquals('URL', $helper('resource'));
    }

    public function testCanOverrideRequestQueryParams(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn([]);

        $this->router->generateUri('resource', [], [])->willReturn('URL');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->wilLReturn(['foo' => 'bar']);

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());
        $helper->setRequest($request->reveal());

        $this->assertEquals('URL?foo=foo', $helper('resource', [], ['foo' => 'foo']));
    }
}
