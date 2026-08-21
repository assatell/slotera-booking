<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

final class PaymentResult
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function to_array(): array
    {
        return $this->data;
    }
}
