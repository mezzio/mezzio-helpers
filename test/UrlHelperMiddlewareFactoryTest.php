<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Helper;

use Interop\Container\ContainerInterface;
use Mezzio\Application;
use Mezzio\Helper\Exception\MissingHelperException;
use Mezzio\Helper\Exception\MissingSubjectException;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Helper\UrlHelperMiddlewareFactory;
use Mezzio\Router\RouteResultSubjectInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class UrlHelperMiddlewareFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function injectContainer($name, $service)
    {
        $service = $service instanceof ObjectProphecy ? $service->reveal() : $service;
        $this->container->has($name)->willReturn(true);
        $this->container->get($name)->willReturn($service);
    }

    public function testFactoryCreatesAndReturnsMiddlewareWhenHelperAndSubjectArePresentInContainer()
    {
        $helper = $this->prophesize(UrlHelper::class)->reveal();
        $subject = $this->prophesize(RouteResultSubjectInterface::class)->reveal();
        $this->injectContainer(UrlHelper::class, $helper);
        $this->injectContainer(RouteResultSubjectInterface::class, $subject);

        $factory = new UrlHelperMiddlewareFactory();
        $middleware =$factory($this->container->reveal());
        $this->assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        $this->assertAttributeSame($helper, 'helper', $middleware);
        $this->assertAttributeSame($subject, 'subject', $middleware);
    }

    public function testFactoryCreatesAndReturnsMiddlewareWhenHelperAndApplicationArePresentInContainer()
    {
        $helper = $this->prophesize(UrlHelper::class)->reveal();
        $subject = $this->prophesize(RouteResultSubjectInterface::class)->reveal();
        $this->injectContainer(UrlHelper::class, $helper);
        $this->container->has(RouteResultSubjectInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Router\RouteResultSubjectInterface::class)->willReturn(false);
        $this->injectContainer(Application::class, $subject);

        $factory = new UrlHelperMiddlewareFactory();
        $middleware =$factory($this->container->reveal());
        $this->assertInstanceOf(UrlHelperMiddleware::class, $middleware);
        $this->assertAttributeSame($helper, 'helper', $middleware);
        $this->assertAttributeSame($subject, 'subject', $middleware);
    }

    public function testFactoryRaisesExceptionWhenContainerDoesNotContainHelper()
    {
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Helper\UrlHelper::class)->willReturn(false);
        $this->injectContainer(
            RouteResultSubjectInterface::class,
            $this->prophesize(RouteResultSubjectInterface::class)
        );
        $factory = new UrlHelperMiddlewareFactory();
        $this->setExpectedException(MissingHelperException::class);
        $middleware =$factory($this->container->reveal());
    }

    public function testFactoryRaisesExceptionWhenContainerDoesNotContainSubject()
    {
        $this->injectContainer(UrlHelper::class, $this->prophesize(UrlHelper::class));
        $this->container->has(RouteResultSubjectInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Router\RouteResultSubjectInterface::class)->willReturn(false);
        $this->container->has(Application::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Application::class)->willReturn(false);
        $factory = new UrlHelperMiddlewareFactory();
        $this->setExpectedException(MissingSubjectException::class);
        $middleware =$factory($this->container->reveal());
    }
}
