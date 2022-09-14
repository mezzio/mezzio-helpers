<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use Psr\Http\Message\UriInterface;

use function assert;
use function preg_match;
use function rtrim;

/**
 * Helper class for generating a fully-qualified URI when provided a path.
 */
class ServerUrlHelper
{
    private ?UriInterface $uri = null;

    /**
     * Return a path relative to the current request URI.
     *
     * If no request URI has been injected, it returns an absolute path
     * only; relative paths are made absolute, and absolute paths are returned
     * verbatim (null paths are returned as root paths).
     *
     * Otherwise, returns a fully-qualified URI based on the injected request
     * URI; absolute paths replace the request URI path, while relative paths
     * are appended to it (and null paths are considered the current path).
     *
     * The $path may optionally contain the query string and/or fragment to
     * use.
     */
    public function __invoke(?string $path = null): string
    {
        $path ??= '';

        if ($this->uri instanceof UriInterface) {
            return $this->createUrlFromUri($path);
        }

        if (empty($path)) {
            return '/';
        }

        if ('/' === $path[0]) {
            return $path;
        }

        return '/' . $path;
    }

    /**
     * Generate a path relative to the current request URI.
     *
     * Proxies to __invoke().
     */
    public function generate(?string $path = null): string
    {
        return $this($path);
    }

    public function setUri(UriInterface $uri): void
    {
        $this->uri = $uri;
    }

    private function createUrlFromUri(string $specification): string
    {
        preg_match(
            '%^(?P<path>[^?#]*)(?:(?:\?(?P<query>[^#]*))?(?:\#(?P<fragment>.*))?)$%',
            (string) $specification,
            $matches
        );
        $path     = $matches['path'];
        $query    = $matches['query'] ?? '';
        $fragment = $matches['fragment'] ?? '';

        assert($this->uri instanceof UriInterface);

        $uri = $this->uri
            ->withQuery('')
            ->withFragment('');

        // Relative path
        if (! empty($path) && '/' !== $path[0]) {
            $path = rtrim($this->uri->getPath(), '/') . '/' . $path;
        }

        // Path present; set on URI
        if (! empty($path)) {
            $uri = $uri->withPath($path);
        }

        // Query present; set on URI
        if (! empty($query)) {
            $uri = $uri->withQuery($query);
        }

        // Fragment present; set on URI
        if (! empty($fragment)) {
            $uri = $uri->withFragment($fragment);
        }

        return (string) $uri;
    }
}
