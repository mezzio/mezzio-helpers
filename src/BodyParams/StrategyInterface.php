<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper\BodyParams;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface defining a body parameter strategy.
 */
interface StrategyInterface
{
    /**
     * Match the content type to the strategy criteria.
     *
     * @param string $contentType
     * @return bool Whether or not the strategy matches.
     */
    public function match($contentType);

    /**
     * Parse the body content and return a new request.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function parse(ServerRequestInterface $request);
}
