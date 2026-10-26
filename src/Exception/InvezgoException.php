<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * Base class for every exception thrown by the SDK.
 *
 * Catch this to handle all SDK failures (API errors, rate limits and connection problems) in one place.
 */
class InvezgoException extends \RuntimeException
{
}
