<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * No usable HTTP answer: DNS/TLS failure, refused connection, timeout, or a 2xx body that is not JSON.
 */
class ConnectionException extends InvezgoException
{
}
