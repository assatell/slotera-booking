<?php

declare(strict_types=1);

namespace Slotera\Application\TranslationQuality;

if (!defined('ABSPATH')) { exit; }

final class ScanIssue
{
    private string $locale;
    private string $section;
    private string $key;
    private string $type;
    private string $severity;
    private string $message;
    private string $source;
    private string $translation;
    private array $context;

    public function __construct(
        string $locale,
        string $section,
        string $key,
        string $type,
        string $severity,
        string $message,
        string $source,
        string $translation,
        array $context = []
    ) {
        $this->locale = $locale;
        $this->section = $section;
        $this->key = $key;
        $this->type = $type;
        $this->severity = $severity;
        $this->message = $message;
        $this->source = $source;
        $this->translation = $translation;
        $this->context = $context;
    }

    public function type(): string { return $this->type; }
    public function severity(): string { return $this->severity; }

    public function to_array(): array
    {
        return [
            'locale' => $this->locale,
            'section' => $this->section,
            'key' => $this->key,
            'type' => $this->type,
            'severity' => $this->severity,
            'issue' => $this->message,
            'source' => $this->truncate($this->source),
            'value' => $this->truncate($this->translation),
            'translation' => $this->truncate($this->translation),
            'context' => $this->context,
        ];
    }

    private function truncate(string $value): string
    {
        $plain = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($value) : strip_tags($value);
        return function_exists('mb_substr') ? mb_substr($plain, 0, 240) : substr($plain, 0, 240);
    }
}
