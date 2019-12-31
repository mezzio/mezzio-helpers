<?php

/**
 * @see       https://github.com/mezzio/mezzio-helpers for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-helpers/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-helpers/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Helper\Exception;

use DomainException;
use Interop\Container\Exception\ContainerException;

class MissingHelperException extends DomainException implements
    ContainerException,
    ExceptionInterface
{
}
