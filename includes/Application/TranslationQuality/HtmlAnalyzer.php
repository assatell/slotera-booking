<?php

declare(strict_types=1);

namespace Slotera\Application\TranslationQuality;

if (!defined('ABSPATH')) { exit; }

final class HtmlAnalyzer
{
    private const VOID_TAGS = ['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'];
    private const REQUIRED_ATTRIBUTES = ['a'=>['href'], 'img'=>['src','alt']];

    public function analyze(string $source, string $translation): array
    {
        if (!$this->contains_html($source) && !$this->contains_html($translation)) {
            return ['valid'=>true,'errors'=>[],'expected_tags'=>[],'found_tags'=>[]];
        }

        $errors = array_merge($this->syntax_errors($translation), $this->structure_errors($source, $translation));
        return [
            'valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'expected_tags' => $this->tag_counts($source),
            'found_tags' => $this->tag_counts($translation),
        ];
    }

    private function contains_html(string $value): bool
    {
        return preg_match('/<\/?[a-z][^>]*>/i', $value) === 1;
    }

    private function syntax_errors(string $html): array
    {
        $errors = [];
        if (!$this->contains_html($html)) { return $errors; }
        if (class_exists('DOMDocument')) {
            $previous = libxml_use_internal_errors(true);
            $document = new \DOMDocument();
            $ok = $document->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            foreach (libxml_get_errors() as $error) {
                if ($error->level >= LIBXML_ERR_ERROR) { $errors[] = trim($error->message); }
            }
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            if (!$ok) { $errors[] = 'HTML could not be parsed.'; }
        } else {
            $stack = [];
            preg_match_all('/<\/?([a-z][a-z0-9]*)\b[^>]*>/i', $html, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $full = $match[0]; $tag = strtolower($match[1]);
                if (in_array($tag, self::VOID_TAGS, true) || str_ends_with(trim($full), '/>')) { continue; }
                if (str_starts_with($full, '</')) {
                    if (array_pop($stack) !== $tag) { $errors[] = 'Mismatched closing tag: ' . $tag; }
                } else { $stack[] = $tag; }
            }
            if ($stack !== []) { $errors[] = 'Unclosed tags: ' . implode(', ', $stack); }
        }
        return $errors;
    }

    private function structure_errors(string $source, string $translation): array
    {
        $errors = [];
        $expected = $this->tag_counts($source);
        $found = $this->tag_counts($translation);
        foreach ($expected as $tag => $count) {
            if (($found[$tag] ?? 0) < $count) { $errors[] = 'Missing required <'.$tag.'> tag.'; }
        }
        foreach (self::REQUIRED_ATTRIBUTES as $tag => $attributes) {
            $expectedAttributes = $this->attributes($source, $tag);
            $foundAttributes = $this->attributes($translation, $tag);
            foreach ($expectedAttributes as $index => $sourceAttrs) {
                foreach ($attributes as $attribute) {
                    if (isset($sourceAttrs[$attribute]) && !isset($foundAttributes[$index][$attribute])) {
                        $errors[] = 'Missing required '.$attribute.' attribute on <'.$tag.'>.';
                    }
                }
            }
        }
        return $errors;
    }

    private function tag_counts(string $html): array
    {
        $counts = [];
        preg_match_all('/<([a-z][a-z0-9]*)\b[^>]*>/i', $html, $matches);
        foreach (($matches[1] ?? []) as $tag) {
            $tag = strtolower((string)$tag);
            $counts[$tag] = ($counts[$tag] ?? 0) + 1;
        }
        ksort($counts);
        return $counts;
    }

    private function attributes(string $html, string $tag): array
    {
        $result = [];
        preg_match_all('/<'.preg_quote($tag, '/').'\b([^>]*)>/i', $html, $matches);
        foreach (($matches[1] ?? []) as $raw) {
            $attrs = [];
            preg_match_all('/([a-z_:][-a-z0-9_:.]*)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', (string)$raw, $attributeMatches);
            foreach (($attributeMatches[1] ?? []) as $name) { $attrs[strtolower((string)$name)] = true; }
            $result[] = $attrs;
        }
        return $result;
    }
}
