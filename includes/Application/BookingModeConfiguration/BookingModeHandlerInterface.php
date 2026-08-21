<?php

declare(strict_types=1);

namespace Slotera\Application\BookingModeConfiguration;

if (!defined('ABSPATH')) {
    exit;
}

interface BookingModeHandlerInterface
{
    public function mode(): string;

    /**
     * @param array<string,mixed> $common_config
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public function sanitize_config(array $common_config, array $source): array;

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public function package_fields(array $config): array;
}
