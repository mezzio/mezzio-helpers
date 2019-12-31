<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper\BodyParams;

use Psr\Http\Message\ServerRequestInterface;

class FormUrlEncodedStrategy implements StrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function match($contentType)
    {
        return (bool) preg_match('#^application/x-www-form-urlencoded($|[ ;])#', $contentType);
    }

    /**
     * {@inheritDoc}
     */
    public function parse(ServerRequestInterface $request)
    {
        $parsedBody = $request->getParsedBody();

        if (!empty($parsedBody)) {
            return $request;
        }

        $rawBody = (string) $request->getBody();

        if (empty($rawBody)) {
            return $request;
        }

        parse_str($rawBody, $parsedBody);

        return $request->withParsedBody($parsedBody);
    }
}
