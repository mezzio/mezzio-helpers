<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use InvalidArgumentException;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-type UrlGeneratorOptions = array{
 *     router?: array<array-key, mixed>,
 *     reuse_result_params?: bool,
 *     reuse_query_params?: bool,
 * }
 */
interface UrlHelperInterface
{
    /**
     * Generate a URL based on a given route.
     *
     * @param non-empty-string|null $routeName
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $queryParams
     * @param UrlGeneratorOptions $options Can have the following keys:
     *     - router (array): contains options to be passed to the router
     *     - reuse_result_params (bool): indicates if the current RouteResult parameters will be used, defaults to true
     *     - reuse_query_params (bool): indicates if the current query parameters will be used, defaults to false
     * @throws Exception\RuntimeException For attempts to use the currently matched route but routing failed.
     * @throws Exception\RuntimeException For attempts to use a matched result
     *     when none has been previously injected in the instance.
     * @throws InvalidArgumentException For malformed fragment identifiers.
     */
    public function __invoke(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ): string;

    /**
     * Generate a URL based on a given route.
     *
     * @param non-empty-string|null $routeName
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $queryParams
     * @param UrlGeneratorOptions $options Can have the following keys:
     *     - router (array): contains options to be passed to the router
     *     - reuse_result_params (bool): indicates if the current RouteResult parameters will be used, defaults to true
     *     - reuse_query_params (bool): indicates if the current query parameters will be used, defaults to false
     * @throws Exception\RuntimeException For attempts to use the currently matched route but routing failed.
     * @throws Exception\RuntimeException For attempts to use a matched result
     *     when none has been previously injected in the instance.
     * @throws InvalidArgumentException For malformed fragment identifiers.
     */
    public function generate(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ): string;

    /**
     * Make the current request available to the helper so that it can re-use query parameters if desired
     */
    public function setRequest(ServerRequestInterface $request): void;

    /**
     * Make the current routing result available to the helper so that it can re-use matched parameters if desired
     */
    public function setRouteResult(RouteResult $result): void;
}
