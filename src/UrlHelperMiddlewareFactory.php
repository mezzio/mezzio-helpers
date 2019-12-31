<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper;

use Interop\Container\ContainerInterface;
use Mezzio\Application;
use Mezzio\Router\RouteResultSubjectInterface;

class UrlHelperMiddlewareFactory
{
    /**
     * Create and return a UrlHelperMiddleware instance.
     *
     * @param ContainerInterface $container
     * @return UrlHelperMiddleware
     * @throws Exception\MissingHelperException if the UrlHelper service is
     *     missing
     * @throws Exception\MissingSubjectException if the
     *     RouteResultSubjectInterface service is missing
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(UrlHelper::class)
            && ! $container->has(\Zend\Expressive\Helper\UrlHelper::class)
        ) {
            throw new Exception\MissingHelperException(sprintf(
                '%s requires a %s service at instantiation; none found',
                UrlHelperMiddleware::class,
                UrlHelper::class
            ));
        }

        $subjectService = $this->getSubjectService($container);

        return new UrlHelperMiddleware(
            $container->has(UrlHelper::class) ? $container->get(UrlHelper::class) : $container->get(\Zend\Expressive\Helper\UrlHelper::class),
            $container->get($subjectService)
        );
    }

    /**
     * Determine the name of the service returning the RouteResultSubjectInterface instance.
     *
     * Checks against:
     *
     * - RouteResultSubjectInterface
     * - Application
     *
     * returning the first that is found in the container.
     *
     * If neither is found, raises an exception.
     *
     * @param ContainerInterface $container
     * @return string
     * @throws Exception\MissingSubjectException
     */
    private function getSubjectService(ContainerInterface $container)
    {
        if ($container->has(RouteResultSubjectInterface::class)) {
            return RouteResultSubjectInterface::class;
        }

        if ($container->has(Application::class)) {
            return Application::class;
        }

        throw new Exception\MissingSubjectException(sprintf(
            '%s requires a %s service at instantiation; none found',
            UrlHelperMiddleware::class,
            UrlHelper::class
        ));
    }
}
