<?php

declare(strict_types=1);

namespace Mezzio\Helper\BodyParams;

use Psr\Http\Message\ServerRequestInterface;

use function parse_str;
use function preg_match;

class FormUrlEncodedStrategy implements StrategyInterface
{
    public function match(string $contentType): bool
    {
        return 1 === preg_match('#^application/x-www-form-urlencoded($|[ ;])#', $contentType);
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $parsedBody = $request->getParsedBody();

        if (! empty($parsedBody)) {
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
