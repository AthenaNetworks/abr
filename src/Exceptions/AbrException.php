<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Exceptions;

use RuntimeException;

/**
 * Base class for every exception thrown by this package.
 *
 * Catch this to handle any ABR failure in one place.
 */
abstract class AbrException extends RuntimeException
{
}
