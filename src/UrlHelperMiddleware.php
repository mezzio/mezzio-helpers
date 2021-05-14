<?php

declare(strict_types=1);

namespace Mezzio\Helper;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Pipeline middleware for injecting a UrlHelper with a RouteResult.
 */
class UrlHelperMiddleware implements MiddlewareInterface
{
    /** @var UrlHelper */
    private $helper;

    public function __construct(UrlHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Inject the helper with the request instance.
     *
     * Inject the UrlHelper instance with a RouteResult, if present as a request attribute.
     * Injects the helper, and then dispatches the next middleware.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->helper->setRequest($request);

        $result = $request->getAttribute(RouteResult::class, false);

        if ($result instanceof RouteResult) {
            $this->helper->setRouteResult($result);
        }

        return $handler->handle($request);
    }
}
