<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware to inject a Content-Length response header.
 *
 * If the response returned by a delegate does not contain a Content-Length
 * header, and the body size is non-null, this middleware will return a new
 * response that contains a Content-Length header based on the body size.
 */
class ContentLengthMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);
        if ($response->hasHeader('Content-Length')) {
            return $response;
        }

        $body = $response->getBody();
        if (null === $body->getSize()) {
            return $response;
        }

        return $response->withHeader('Content-Length', (string) $body->getSize());
    }
}
