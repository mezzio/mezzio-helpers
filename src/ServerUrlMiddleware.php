<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerUrlMiddleware
{
    /**
     * @var ServerUrlHelper
     */
    private $helper;

    /**
     * @param ServerUrlHelper $helper
     */
    public function __construct(ServerUrlHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Inject the ServerUrlHelper instance with the request URI.
     *
     * Injects the ServerUrlHelper with the incoming request URI, and then invoke
     * the next middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->helper->setUri($request->getUri());
        return $next($request, $response);
    }
}
