<?php

declare(strict_types=1);

namespace Mezzio\Helper\Template;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware for initializing the template variable container for the current request.
 *
 * If no template variable container exists yet in the request, this middleware
 * will create one and inject it in the request passed to the handler.
 *
 * Otherwise, it does nothing.
 *
 * The middleware uses a key named after the container class.
 */
class TemplateVariableContainerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $container = $request->getAttribute(TemplateVariableContainer::class);

        if ($container instanceof TemplateVariableContainer) {
            return $handler->handle($request);
        }

        $container = new TemplateVariableContainer();
        return $handler->handle($request->withAttribute(
            TemplateVariableContainer::class,
            $container
        ));
    }
}
