<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Exceptions;

use Throwable;

/**
 * The ABR returned an error that does not map to a more specific exception.
 */
class ServiceException extends AbrException
{
    public function __construct(
        string $message,
        public readonly ?string $abrCode = null,
        public readonly ?int $httpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
