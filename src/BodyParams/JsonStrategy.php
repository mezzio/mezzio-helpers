<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper\BodyParams;

use Psr\Http\Message\ServerRequestInterface;

class JsonStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function match($contentType)
    {
        $parts = explode(';', $contentType);
        $mime = array_shift($parts);
        return (bool) preg_match('#[/+]json$#', trim($mime));
    }

    /**
     * {@inheritDoc}
     */
    public function parse(ServerRequestInterface $request)
    {
        $rawBody = (string) $request->getBody();
        return $request
            ->withAttribute('rawBody', $rawBody)
            ->withParsedBody(json_decode($rawBody, true));
    }
}
