<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detects active WordPress plugins that commonly own outbound mail delivery.
 *
 * Slotera deliberately detects plugin directory slugs rather than main-file
 * names so the check remains stable across plugin packaging changes.
 */
final class ExternalMailPluginDetector
{
    /** @var array<string,string> */
    private const RECOGNIZED_PLUGIN_SLUGS = [
        'wp-mail-smtp' => 'WP Mail SMTP',
        'wp-mail-smtp-pro' => 'WP Mail SMTP Pro',
        'fluent-smtp' => 'FluentSMTP',
        'post-smtp' => 'Post SMTP',
        'easy-wp-smtp' => 'Easy WP SMTP',
        'mailgun' => 'Mailgun for WordPress',
        'mailgun-for-wordpress' => 'Mailgun for WordPress',
        'mailin' => 'Brevo / Sendinblue',
        'brevo' => 'Brevo',
        'sendgrid-email-delivery-simplified' => 'SendGrid',
        'wp-offload-ses' => 'WP Offload SES',
        'wp-ses' => 'WP SES',
        'smtp2go' => 'SMTP2GO',
        'mailjet-for-wordpress' => 'Mailjet for WordPress',
        'sparkpost' => 'SparkPost',
    ];

    /**
     * @return array<int,array{slug:string,name:string,basename:string,network:bool}>
     */
    public function detected(): array
    {
        $signatures = apply_filters('sltr_external_mail_plugin_signatures', self::RECOGNIZED_PLUGIN_SLUGS);
        if (!is_array($signatures)) {
            $signatures = self::RECOGNIZED_PLUGIN_SLUGS;
        }

        $active = get_option('active_plugins', []);
        if (!is_array($active)) {
            $active = [];
        }

        $network = is_multisite() ? get_site_option('active_sitewide_plugins', []) : [];
        if (!is_array($network)) {
            $network = [];
        }

        $matches = [];
        foreach ($active as $basename) {
            $match = $this->match_basename((string) $basename, $signatures, false);
            if ($match !== null) {
                $matches[$match['slug']] = $match;
            }
        }
        foreach (array_keys($network) as $basename) {
            $match = $this->match_basename((string) $basename, $signatures, true);
            if ($match !== null) {
                $matches[$match['slug']] = $match;
            }
        }

        return array_values($matches);
    }

    public function has_external_delivery_plugin(): bool
    {
        return $this->detected() !== [];
    }

    public function detected_names(): string
    {
        $names = array_map(static fn(array $plugin): string => $plugin['name'], $this->detected());
        return implode(', ', array_values(array_unique($names)));
    }

    /**
     * @param array<string,string> $signatures
     * @return array{slug:string,name:string,basename:string,network:bool}|null
     */
    private function match_basename(string $basename, array $signatures, bool $network): ?array
    {
        $basename = trim(str_replace('\\', '/', $basename));
        if ($basename === '') {
            return null;
        }

        $parts = explode('/', $basename, 2);
        $slug = sanitize_key((string) ($parts[0] ?? ''));
        if ($slug === '' || !isset($signatures[$slug])) {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => sanitize_text_field((string) $signatures[$slug]),
            'basename' => $basename,
            'network' => $network,
        ];
    }
}
