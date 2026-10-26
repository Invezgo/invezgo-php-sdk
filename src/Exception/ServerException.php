<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * The API failed or is temporarily unavailable (HTTP 5xx). Safe to retry later.
 */
class ServerException extends ApiException
{
}
