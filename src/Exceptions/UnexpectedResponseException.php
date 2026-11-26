<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Exceptions;

/**
 * The ABR returned something this package could not understand (non-XML, unknown structure, ...).
 */
final class UnexpectedResponseException extends AbrException
{
}
