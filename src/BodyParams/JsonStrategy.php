<?php

declare(strict_types=1);

namespace Mezzio\Helper\BodyParams;

use Mezzio\Helper\Exception\MalformedRequestBodyException;
use Psr\Http\Message\ServerRequestInterface;

use function is_array;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function sprintf;

use const JSON_ERROR_NONE;

class JsonStrategy implements StrategyInterface
{
    public function match(string $contentType): bool
    {
        return 1 === preg_match('#^application/(|[\S]+\+)json($|[ ;])#', $contentType);
    }

    /**
     * {@inheritDoc}
     *
     * @throws MalformedRequestBodyException
     */
    public function parse(ServerRequestInterface $request): ServerRequestInterface
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

        if (! is_array($parsedBody)) {
            $parsedBody = null;
        }

        return $request
            ->withAttribute('rawBody', $rawBody)
            ->withParsedBody($parsedBody);
    }
}
