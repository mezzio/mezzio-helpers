<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use ArrayObject;
use Mezzio\Helper\Exception\RuntimeException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Exception\RuntimeException as RouterException;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouteResultObserverInterface;
use Mezzio\Router\RouterInterface;
use PHPUnit_Framework_TestCase as TestCase;

class UrlHelperTest extends TestCase
{
    public function setUp()
    {
        $this->router = $this->prophesize(RouterInterface::class);
    }

    public function createHelper()
    {
        return new UrlHelper($this->router->reveal());
    }

    public function testRaisesExceptionOnInvocationIfNoRouteProvidedAndNoResultPresent()
    {
        $helper = $this->createHelper();
        $this->setExpectedException(RuntimeException::class, 'use matched result');
        $helper();
    }

    public function testRaisesExceptionOnInvocationIfNoRouteProvidedAndResultIndicatesFailure()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(true);
        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());
        $this->setExpectedException(RuntimeException::class, 'routing failed');
        $helper();
    }

    public function testRaisesExceptionOnInvocationIfRouterCannotGenerateUriForRouteProvided()
    {
        $this->router->generateUri('foo', [])->willThrow(RouterException::class);
        $helper = $this->createHelper();
        $this->setExpectedException(RouterException::class);
        $helper('foo');
    }

    public function testWhenNoRouteProvidedTheHelperUsesComposedResultToGenerateUrl()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('foo');
        $result->getMatchedParams()->willReturn(['bar' => 'baz']);

        $this->router->generateUri('foo', ['bar' => 'baz'])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper());
    }

    public function testWhenNoRouteProvidedTheHelperMergesPassedParametersWithResultParametersToGenerateUrl()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('foo');
        $result->getMatchedParams()->willReturn(['bar' => 'baz']);

        $this->router->generateUri('foo', ['bar' => 'baz', 'baz' => 'bat'])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper(null, ['baz' => 'bat']));
    }

    public function testWhenRouteProvidedTheHelperDelegatesToTheRouterToGenerateUrl()
    {
        $this->router->generateUri('foo', ['bar' => 'baz'])->willReturn('URL');
        $helper = $this->createHelper();
        $this->assertEquals('URL', $helper('foo', ['bar' => 'baz']));
    }

    public function testIfRouteResultRouteNameDoesNotMatchRequestedNameItWillNotMergeParamsToGenerateUri()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('not-resource');
        $result->getMatchedParams()->shouldNotBeCalled();

        $this->router->generateUri('resource', [])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource'));
    }

    public function testMergesRouteResultParamsWithProvidedParametersToGenerateUri()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn(['id' => 1]);

        $this->router->generateUri('resource', ['id' => 1, 'version' => 2])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource', ['version' => 2]));
    }

    public function testProvidedParametersOverrideAnyPresentInARouteResultWhenGeneratingUri()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRouteName()->willReturn('resource');
        $result->getMatchedParams()->willReturn(['id' => 1]);

        $this->router->generateUri('resource', ['id' => 2])->willReturn('URL');

        $helper = $this->createHelper();
        $helper->setRouteResult($result->reveal());

        $this->assertEquals('URL', $helper('resource', ['id' => 2]));
    }

    public function testIsARouteResultObserver()
    {
        $helper = $this->createHelper();
        $this->assertInstanceOf(RouteResultObserverInterface::class, $helper);
    }

    public function testUpdateMethodSetsRouteResultProperty()
    {
        $result = $this->prophesize(RouteResult::class);
        $helper = $this->createHelper();
        $helper->update($result->reveal());
        $this->assertAttributeSame($result->reveal(), 'result', $helper);
    }

    public function testAllowsSettingBasePath()
    {
        $helper = $this->createHelper();
        $helper->setBasePath('/foo');
        $this->assertAttributeEquals('/foo', 'basePath', $helper);
    }

    public function testSlashIsPrependedWhenBasePathDoesNotHaveOne()
    {
        $helper = $this->createHelper();
        $helper->setBasePath('foo');
        $this->assertAttributeEquals('/foo', 'basePath', $helper);
    }

    public function testBasePathIsPrependedToGeneratedPath()
    {
        $this->router->generateUri('foo', ['bar' => 'baz'])->willReturn('/foo/baz');
        $helper = $this->createHelper();
        $helper->setBasePath('/prefix');
        $this->assertEquals('/prefix/foo/baz', $helper('foo', ['bar' => 'baz']));
    }
}
