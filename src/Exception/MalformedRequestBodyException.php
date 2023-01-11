<?php

declare(strict_types=1);

namespace Mezzio\Helper\Exception;

use Exception;
use InvalidArgumentException;

/** @final */
class MalformedRequestBodyException extends InvalidArgumentException implements ExceptionInterface
{
    /** @param string $message */
    public function __construct($message, ?Exception $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
