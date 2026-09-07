<?php

declare(strict_types=1);

namespace Slotera\Application\Support;

if (!defined('ABSPATH')) { exit; }

final class UnicodeText
{
    public static function limit(string $value, int $max_characters): string
    {
        if ($value === '' || $max_characters <= 0) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, 0, $max_characters, 'UTF-8');
        }

        $matched = preg_match_all('/./us', $value, $characters);
        if ($matched === false) {
            return '';
        }

        return implode('', array_slice($characters[0], 0, $max_characters));
    }
}
