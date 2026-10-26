<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * The API key is missing, invalid or expired (HTTP 401).
 */
class AuthenticationException extends ApiException
{
}
