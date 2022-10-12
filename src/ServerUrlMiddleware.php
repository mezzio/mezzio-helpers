<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use Mezzio\Helper\ServerUrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ServerUrlMiddleware implements MiddlewareInterface
{
    public function __construct(private ServerUrlHelper $helper)
    {
    }

    /**
     * Inject the ServerUrlHelper instance with the request URI.
     * Injects the ServerUrlHelper with the incoming request URI, and then invoke
     * the next middleware.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->helper->setUri($request->getUri());
        return $handler->handle($request);
    }
}
