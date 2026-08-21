<?php

declare(strict_types=1);

namespace Slotera\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class HtmlSanitizer
{
    public static function public_content(string $html, bool $allow_shortcodes = false): string
    {
        $html = wp_unslash($html);

        if (!$allow_shortcodes) {
            $html = self::strip_external_shortcodes($html);
        }

        return wp_kses($html, self::allowed_public_html());
    }

    public static function render_public_content(string $html, bool $autop = false, bool $allow_shortcodes = false): string
    {
        if ($allow_shortcodes) {
            $html = self::public_content($html, true);
            $html = do_shortcode($html);
            $html = self::public_content($html, true);
        } else {
            $html = self::render_internal_package_shortcodes($html);
            $html = self::public_content($html, false);
        }

        return $autop ? wpautop($html) : $html;
    }

    public static function allow_package_shortcodes(): bool
    {
        return (bool) apply_filters('slotera_allow_package_shortcodes', false);
    }


    private static function strip_external_shortcodes(string $html): string
    {
        $placeholders = [];
        $html = preg_replace_callback(
            '/\[(slotera_package_slider|slotera_package_image|slotera_package_media|slotera_package_text_block|slotera_contact)(\s[^\]]*)?\]/',
            static function (array $matches) use (&$placeholders): string {
                $key = 'SLTR_INTERNAL_SHORTCODE_' . count($placeholders) . '_PLACEHOLDER';
                $placeholders[$key] = $matches[0];
                return $key;
            },
            $html
        ) ?? $html;

        $html = strip_shortcodes($html);

        foreach ($placeholders as $key => $shortcode) {
            $html = str_replace($key, $shortcode, $html);
        }

        return $html;
    }

    private static function render_internal_package_shortcodes(string $html): string
    {
        return preg_replace_callback(
            '/\[(slotera_package_slider|slotera_package_image|slotera_package_media|slotera_package_text_block|slotera_contact)(\s[^\]]*)?\]/',
            static function (array $matches): string {
                return do_shortcode($matches[0]);
            },
            $html
        ) ?? $html;
    }

    private static function allowed_public_html(): array
    {
        $allowed = [
            'a' => [
                'href' => true,
                'title' => true,
                'target' => true,
                'rel' => true,
            ],
            'abbr' => ['title' => true],
            'b' => [],
            'blockquote' => ['cite' => true],
            'br' => [],
            'button' => [
                'aria-label' => true,
                'class' => true,
                'data-full' => true,
                'data-sltr-video-unmute' => true,
                'type' => true,
            ],
            'form' => [
                'action' => true,
                'class' => true,
                'method' => true,
                'novalidate' => true,
                'data-sltr-recaptcha-v3-action' => true,
            ],
            'input' => [
                'autocomplete' => true,
                'class' => true,
                'id' => true,
                'maxlength' => true,
                'name' => true,
                'placeholder' => true,
                'required' => true,
                'type' => true,
                'value' => true,
                'data-sltr-recaptcha-v3-token' => true,
            ],
            'label' => [
                'class' => true,
                'for' => true,
            ],
            'textarea' => [
                'class' => true,
                'id' => true,
                'maxlength' => true,
                'name' => true,
                'placeholder' => true,
                'required' => true,
                'rows' => true,
            ],
            'cite' => [],
            'code' => [],
            'del' => ['datetime' => true],
            'div' => [
                'class' => true,
                'data-speed' => true,
                'data-current' => true,
                'data-sitekey' => true,
                'data-theme' => true,
                'data-size' => true,
                'data-action' => true,
                'aria-label' => true,
                'style' => true,
            ],
            'em' => [],
            'h2' => ['class' => true, 'style' => true],
            'h3' => ['class' => true],
            'h4' => ['class' => true],
            'hr' => [],
            'i' => [],
            'img' => [
                'alt' => true,
                'class' => true,
                'height' => true,
                'loading' => true,
                'src' => true,
                'srcset' => true,
                'sizes' => true,
                'title' => true,
                'width' => true,
                'style' => true,
                'data-focus-x' => true,
                'data-focus-y' => true,
            ],
            'video' => [
                'autoplay' => true,
                'class' => true,
                'controls' => true,
                'height' => true,
                'loop' => true,
                'muted' => true,
                'playsinline' => true,
                'poster' => true,
                'preload' => true,
                'width' => true,
            ],
            'source' => [
                'src' => true,
                'type' => true,
            ],
            'li' => ['class' => true, 'style' => true],
            'ol' => ['class' => true],
            'p' => ['class' => true, 'style' => true],
            'pre' => [],
            'span' => ['class' => true, 'aria-hidden' => true],
            'strong' => [],
            'sub' => [],
            'sup' => [],
            'table' => ['class' => true],
            'tbody' => [],
            'td' => ['colspan' => true, 'rowspan' => true],
            'tfoot' => [],
            'th' => ['colspan' => true, 'rowspan' => true],
            'thead' => [],
            'tr' => [],
            'u' => [],
            'ul' => ['class' => true],
        ];

        return (array) apply_filters('slotera_public_content_allowed_html', $allowed);
    }
}
