<?php
declare(strict_types=1);
namespace Slotera\Core;
if (!defined('ABSPATH')) { exit; }

/**
 * Validates SQL identifiers before they are interpolated into raw SQL strings.
 *
 * WordPress placeholders cannot bind table or column identifiers, so Slotera table
 * names are the one allowed exception to the "prepare every dynamic value" rule.
 * Every table name returned by Database::*_table() must pass through this guard.
 *
 * This class only accepts plain same-database identifiers made of ASCII letters,
 * numbers and underscores. It intentionally rejects dots, backticks, spaces and
 * other punctuation so future changes cannot accidentally introduce cross-database
 * names or user-controlled SQL fragments.
 */
final class SafeSqlIdentifier
{
    public static function table(string $identifier): string
    {
        return self::identifier($identifier, 'table');
    }

    public static function column(string $identifier): string
    {
        return self::identifier($identifier, 'column');
    }

    private static function identifier(string $identifier, string $kind): string
    {
        if (!preg_match('/\A[A-Za-z0-9_]+\z/', $identifier)) {
            throw new \InvalidArgumentException('Unsafe Slotera SQL ' . $kind . ' identifier.');
        }

        return $identifier;
    }
}
