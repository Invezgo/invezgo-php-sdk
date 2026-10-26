<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * Request parameters or body were rejected (HTTP 400 or 422). See `$message` for the offending field.
 */
class ValidationException extends ApiException
{
}
