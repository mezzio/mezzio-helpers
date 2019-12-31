<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Helper\BodyParams;

use Mezzio\Helper\Exception\MalformedRequestBodyException;
use Psr\Http\Message\ServerRequestInterface;

use function array_shift;
use function explode;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function sprintf;
use function trim;

use const JSON_ERROR_NONE;

class JsonStrategy implements StrategyInterface
{
    public function match(string $contentType) : bool
    {
        $parts = explode(';', $contentType);
        $mime = array_shift($parts);
        return (bool) preg_match('#[/+]json$#', trim($mime));
    }

    /**
     * {@inheritDoc}
     *
     * @throws MalformedRequestBodyException
     */
    public function parse(ServerRequestInterface $request) : ServerRequestInterface
    {
        $rawBody = (string) $request->getBody();

        if (empty($rawBody)) {
            return $request
                ->withAttribute('rawBody', $rawBody)
                ->withParsedBody(null);
        }

        $parsedBody = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new MalformedRequestBodyException(sprintf(
                'Error when parsing JSON request body: %s',
                json_last_error_msg()
            ));
        }

        return $request
            ->withAttribute('rawBody', $rawBody)
            ->withParsedBody($parsedBody);
    }
}
