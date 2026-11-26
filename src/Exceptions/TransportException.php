<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Exceptions;

/**
 * The ABR could not be reached (DNS, connection, timeout, TLS, ...).
 */
final class TransportException extends AbrException
{
}
