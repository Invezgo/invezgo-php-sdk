<?php

declare(strict_types=1);

namespace Invezgo\Internal;

/**
 * Lenient JSON to typed-value converters used by the generated models.
 *
 * Every converter returns null for null/missing/mismatched input so one odd field never breaks a whole
 * response; generated code substitutes defaults for non-nullable fields.
 *
 * @internal
 */
final class Hydrate
{
    public static function string(mixed $v): ?string
    {
        return is_scalar($v) ? (string) $v : null;
    }

    /** Accepts ints, floats and numeric strings (e.g. order IDs sent as strings). */
    public static function int(mixed $v): ?int
    {
        if (is_int($v)) {
            return $v;
        }
        if (is_float($v)) {
            return (int) $v;
        }
        if (is_string($v) && is_numeric($v)) {
            return preg_match('/[.eE]/', $v) === 1 ? (int) (float) $v : (int) $v;
        }
        return null;
    }

    /** Accepts numbers and numeric strings. */
    public static function float(mixed $v): ?float
    {
        return is_int($v) || is_float($v) || (is_string($v) && is_numeric($v)) ? (float) $v : null;
    }

    public static function bool(mixed $v): ?bool
    {
        if (is_bool($v)) {
            return $v;
        }
        return is_int($v) ? $v !== 0 : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function array(mixed $v): ?array
    {
        /** @var array<string, mixed>|null */
        return is_array($v) ? $v : null;
    }

    /**
     * @template T
     * @param callable(mixed): T $item
     * @return list<T>|null
     */
    public static function list(mixed $v, callable $item): ?array
    {
        return is_array($v) ? array_values(array_map($item, $v)) : null;
    }

    /**
     * @template T
     * @param callable(mixed): T $item
     * @return array<string, T>|null
     */
    public static function map(mixed $v, callable $item): ?array
    {
        /** @var array<string, T>|null */
        return is_array($v) ? array_map($item, $v) : null;
    }

    /**
     * @template T
     * @param callable(array<string, mixed>): T $factory
     * @return T|null
     */
    public static function model(mixed $v, callable $factory): mixed
    {
        return is_array($v) ? $factory(self::array($v) ?? []) : null;
    }
}
