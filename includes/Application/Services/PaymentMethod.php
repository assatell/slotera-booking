<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

final class PaymentMethod
{
    private string $id;
    private string $label;
    private string $instructions;
    private bool $test_mode;

    public function __construct(string $id, string $label, string $instructions = '', bool $test_mode = false)
    {
        $this->id = sanitize_key($id);
        $this->label = sanitize_text_field($label);
        $this->instructions = sanitize_textarea_field($instructions);
        $this->test_mode = $test_mode;
    }

    public function get_id(): string { return $this->id; }
    public function get_label(): string { return $this->label !== '' ? $this->label : $this->id; }
    public function get_instructions(): string { return $this->instructions; }
    public function is_test_mode(): bool { return $this->test_mode; }
}
