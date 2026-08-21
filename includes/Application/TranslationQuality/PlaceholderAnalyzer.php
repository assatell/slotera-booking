<?php

declare(strict_types=1);

namespace Slotera\Application\TranslationQuality;

if (!defined('ABSPATH')) { exit; }

final class PlaceholderAnalyzer
{
    public function analyze(string $source, string $translation): array
    {
        $expected = $this->signature($source);
        $found = $this->signature($translation);
        $missing = $this->difference($expected, $found);
        $extra = $this->difference($found, $expected);

        return [
            'valid' => $missing === [] && $extra === [],
            'expected' => $expected,
            'found' => $found,
            'missing' => $missing,
            'extra' => $extra,
            'message' => $this->message($missing, $extra),
        ];
    }

    public function signature(string $text): array
    {
        $tokens = [];
        $patterns = [
            '/(?<!%)%(?:\d+\$)?[-+0 #\']*(?:\d+|\*)?(?:\.(?:\d+|\*))?[bcdeEfFgGosuxX]/',
            '/\{\{\s*[a-zA-Z0-9_.-]+\s*\}\}/',
            '/(?<!\{)\{\s*[a-zA-Z0-9_.-]+\s*\}(?!\})/',
            '/\[\s*[a-zA-Z0-9_.-]+\s*\]/',
        ];
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            foreach (($matches[0] ?? []) as $raw) {
                $token = $this->normalize((string)$raw);
                $tokens[$token] = ($tokens[$token] ?? 0) + 1;
            }
        }
        ksort($tokens);
        return $tokens;
    }

    private function normalize(string $token): string
    {
        $token = preg_replace('/\s+/', '', trim($token)) ?? trim($token);
        if (str_starts_with($token, '%')) {
            return strtolower($token);
        }
        return strtolower($token);
    }

    private function difference(array $left, array $right): array
    {
        $difference = [];
        foreach ($left as $token => $count) {
            $remaining = (int)$count - (int)($right[$token] ?? 0);
            if ($remaining > 0) { $difference[$token] = $remaining; }
        }
        return $difference;
    }

    private function message(array $missing, array $extra): string
    {
        $parts = [];
        if ($missing !== []) { $parts[] = 'Missing: ' . $this->format($missing); }
        if ($extra !== []) { $parts[] = 'Extra: ' . $this->format($extra); }
        return $parts === [] ? 'Placeholder signature matches.' : implode('; ', $parts);
    }

    private function format(array $tokens): string
    {
        $parts = [];
        foreach ($tokens as $token => $count) {
            $parts[] = $token . ((int)$count > 1 ? ' × ' . (int)$count : '');
        }
        return implode(', ', $parts);
    }
}
