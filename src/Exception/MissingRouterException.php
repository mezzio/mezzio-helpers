<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Helper\Exception;

use DomainException;
use Psr\Container\ContainerExceptionInterface;

class MissingRouterException extends DomainException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
