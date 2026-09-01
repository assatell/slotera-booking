<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

function sanitize_text_field($value): string
{
    return trim((string) $value);
}

require dirname(__DIR__, 2) . '/includes/Infrastructure/Repositories/BookingRepository.php';

$repository = new \Slotera\Infrastructure\Repositories\BookingRepository();
$method = new ReflectionMethod($repository, 'normalize_end_date');
$method->setAccessible(true);

$cases = [
    '' => null,
    '0000-00-00' => null,
    '2026-02-29' => null,
    'not-a-date' => null,
    '2026-09-01' => '2026-09-01',
];

foreach ($cases as $input => $expected) {
    $actual = $method->invoke($repository, $input);
    if ($actual !== $expected) {
        fwrite(STDERR, sprintf("Unexpected normalization for %s: %s\n", $input, var_export($actual, true)));
        exit(1);
    }
}

echo "OK: booking end dates normalize to nullable valid dates.\n";
