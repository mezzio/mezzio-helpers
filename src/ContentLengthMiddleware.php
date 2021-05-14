<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to inject a Content-Length response header.
 *
 * If the response returned by a handler does not contain a Content-Length
 * header, and the body size is non-null, this middleware will return a new
 * response that contains a Content-Length header based on the body size.
 */
class ContentLengthMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response->hasHeader('Content-Length')) {
            return $response;
        }

        $body     = $response->getBody();
        $bodySize = $body->getSize();
        if (null === $bodySize) {
            return $response;
        }

        return $response->withHeader('Content-Length', (string) $bodySize);
    }
}
