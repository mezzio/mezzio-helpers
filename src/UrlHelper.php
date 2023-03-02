<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use InvalidArgumentException;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_merge;
use function assert;
use function count;
use function http_build_query;
use function is_string;
use function ltrim;
use function preg_match;
use function sprintf;

/**
 * @psalm-import-type UrlGeneratorOptions from UrlHelperInterface
 * @final
 */
class UrlHelper implements UrlHelperInterface
{
    /**
     * Regular expression used to validate fragment identifiers.
     *
     * @see RFC 3986: https://tools.ietf.org/html/rfc3986#section-3.5
     */
    public const FRAGMENT_IDENTIFIER_REGEX = '/^([!$&\'()*+,;=._~:@\/?-]|%[0-9a-fA-F]{2}|[a-zA-Z0-9])+$/';

    private string $basePath = '/';

    private ?RouteResult $result = null;

    private ?ServerRequestInterface $request = null;

    public function __construct(private RouterInterface $router)
    {
    }

    /** @inheritDoc */
    public function __invoke(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ): string {
        $result = $this->getRouteResult();
        if ($routeName === null && $result === null) {
            throw new Exception\RuntimeException(
                'Attempting to use matched result when none was injected; aborting'
            );
        }

        $basePath = $this->getBasePath();
        if ($basePath === '/') {
            $basePath = '';
        }

        // Get the options to be passed to the router
        $routerOptions = $options['router'] ?? [];

        if ($routeName === null) {
            $path = $basePath . $this->generateUriFromResult($routeParams, $result, $routerOptions);
            $path = $this->appendQueryStringArguments($path, $queryParams);
            $path = $this->appendFragment($path, $fragmentIdentifier);
            return $path;
        }

        $reuseResultParams = ! isset($options['reuse_result_params']) || (bool) $options['reuse_result_params'];

        if ($result && $reuseResultParams) {
            // Merge RouteResult with the route parameters
            $routeParams = $this->mergeParams($routeName, $result, $routeParams);
        }

        $reuseQueryParams = isset($options['reuse_query_params']) ? (bool) $options['reuse_query_params'] : false;

        if ($result && $reuseQueryParams) {
            // Merge current request params with passed query params
            $queryParams = $this->mergeQueryParams($routeName, $result, $queryParams);
        }

        // Generate the route
        $path = $basePath . $this->router->generateUri($routeName, $routeParams, $routerOptions);

        // Append query string arguments and fragment, if present
        $path = $this->appendQueryStringArguments($path, $queryParams);
        $path = $this->appendFragment($path, $fragmentIdentifier);

        return $path;
    }

    /** @inheritDoc */
    public function generate(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ): string {
        return $this($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options);
    }

    /**
     * Inject a route result.
     *
     * When the route result is injected, the helper will use it to seed default
     * parameters if the URL being generated is for the route that was matched.
     */
    public function setRouteResult(RouteResult $result): void
    {
        $this->result = $result;
    }

    /**
     * Set the base path to prepend to a generated URI
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = '/' . ltrim($path, '/');
    }

    public function getRouteResult(): ?RouteResult
    {
        return $this->result;
    }

    /**
     * Set request instance
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Internal accessor for retrieving the base path.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @throws Exception\RuntimeException If current result is a routing failure.
     */
    private function generateUriFromResult(array $params, RouteResult $result, array $routerOptions): string
    {
        if ($result->isFailure()) {
            throw new Exception\RuntimeException(
                'Attempting to use matched result when routing failed; aborting'
            );
        }

        $name = $result->getMatchedRouteName();
        assert(is_string($name)); // Cannot be false if the result is not a failure
        $params = array_merge($result->getMatchedParams(), $params);
        return $this->router->generateUri($name, $params, $routerOptions);
    }

    /**
     * Merge route result params and provided parameters.
     *
     * If the route result represents a routing failure, returns the params
     * verbatim.
     *
     * If the route result does not represent the same route name requested,
     * returns the params verbatim.
     *
     * Otherwise, merges the route result params with those provided at
     * invocation, with the latter having precedence.
     *
     * @param string $route Route name.
     * @param RouteResult $result RouteResult instance
     * @param array $params Route parameters
     * @return array Merged parameters
     */
    private function mergeParams(string $route, RouteResult $result, array $params): array
    {
        if ($result->isFailure()) {
            return $params;
        }

        if ($result->getMatchedRouteName() !== $route) {
            return $params;
        }

        return array_merge($result->getMatchedParams(), $params);
    }

    /**
     * Merge requested route query params with existing request query parameters.
     *
     * If route result represents routing failure, returns the params verbatim
     *
     * If the route result does not represent the same route name requested,
     * returns the params verbatim.
     *
     * Otherwise, merges the current request query parameters with the specified query
     * parameters with the latter having precedence.
     *
     * @param string $route Route name
     * @param RouteResult $result RouteResult instance
     * @param array $params Params to be merged with request params
     */
    private function mergeQueryParams(string $route, RouteResult $result, array $params): array
    {
        if ($result->isFailure()) {
            return $params;
        }

        if ($result->getMatchedRouteName() !== $route) {
            return $params;
        }

        return array_merge($this->getRequest()?->getQueryParams() ?? [], $params);
    }

    /**
     * Append query string arguments to a URI string, if any are present.
     */
    private function appendQueryStringArguments(string $uriString, array $queryParams): string
    {
        if (count($queryParams) > 0) {
            return sprintf('%s?%s', $uriString, http_build_query($queryParams));
        }
        return $uriString;
    }

    /**
     * Append a fragment to a URI string, if present.
     *
     * @throws InvalidArgumentException If the fragment identifier is malformed.
     */
    private function appendFragment(string $uriString, ?string $fragmentIdentifier): string
    {
        if ($fragmentIdentifier !== null) {
            if (! preg_match(self::FRAGMENT_IDENTIFIER_REGEX, $fragmentIdentifier)) {
                throw new InvalidArgumentException('Fragment identifier must conform to RFC 3986', 400);
            }

            return sprintf('%s#%s', $uriString, $fragmentIdentifier);
        }
        return $uriString;
    }
}
