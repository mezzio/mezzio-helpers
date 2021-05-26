<?php

declare(strict_types=1);

namespace Mezzio\Helper\Exception;

use DomainException;
use Psr\Container\ContainerExceptionInterface;

class MissingRouterException extends DomainException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
