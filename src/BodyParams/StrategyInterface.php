<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

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
     * @return bool Whether or not the strategy matches.
     */
    public function match(string $contentType): bool;

    /**
     * Parse the body content and return a new request.
     */
    public function parse(ServerRequestInterface $request): ServerRequestInterface;
}
