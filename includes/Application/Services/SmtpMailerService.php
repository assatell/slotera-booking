<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;


}

final class SmtpMailerService
{
    private SettingsRepository $settings;
    private ExternalMailPluginDetector $external_mail_plugins;

    public function __construct(?SettingsRepository $settings = null, ?ExternalMailPluginDetector $external_mail_plugins = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->external_mail_plugins = $external_mail_plugins ?: new ExternalMailPluginDetector();
    }

    public function register_hooks(): void
    {
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
        add_action('wp_mail_failed', [$this, 'log_mail_failure']);
        add_filter('wp_mail_from', [$this, 'filter_mail_from']);
        add_filter('wp_mail_from_name', [$this, 'filter_mail_from_name']);
    }

    public function configure_phpmailer(PHPMailer $phpmailer): void
    {
        if ((int) $this->settings->get('smtp_enabled', 0) !== 1 || $this->external_mail_plugins->has_external_delivery_plugin()) {
            return;
        }

        $host = sanitize_text_field((string) $this->settings->get('smtp_host', ''));
        if ($host === '') {
            return;
        }

        $port = absint((string) $this->settings->get('smtp_port', 587));
        if ($port < 1 || $port > 65535) {
            $port = 587;
        }

        $encryption = strtolower(sanitize_key((string) $this->settings->get('smtp_encryption', 'tls')));
        if (!in_array($encryption, ['none', 'tls', 'ssl'], true)) {
            $encryption = 'tls';
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = $port;
        $phpmailer->SMTPAuth = (int) $this->settings->get('smtp_auth', 1) === 1;
        $phpmailer->SMTPAutoTLS = $encryption === 'tls';

        if ($encryption === 'tls') {
            $phpmailer->SMTPSecure = defined(PHPMailer::class . '::ENCRYPTION_STARTTLS') ? PHPMailer::ENCRYPTION_STARTTLS : 'tls';
        } elseif ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = defined(PHPMailer::class . '::ENCRYPTION_SMTPS') ? PHPMailer::ENCRYPTION_SMTPS : 'ssl';
        } else {
            $phpmailer->SMTPSecure = '';
        }

        if ($phpmailer->SMTPAuth) {
            $phpmailer->Username = (string) $this->settings->get('smtp_username', '');
            $phpmailer->Password = (string) $this->settings->get('smtp_password', '');
        }

        $phpmailer->Timeout = max(10, (int) $this->settings->get('smtp_timeout', 20));

        if ((int) $this->settings->get('smtp_allow_insecure_ssl', 0) === 1) {
            $phpmailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        // Do not force setFrom() here. Slotera already sends From headers and
        // WordPress applies wp_mail_from/wp_mail_from_name filters. Forcing
        // setFrom() inside phpmailer_init can conflict with SMTP providers that
        // require the envelope sender to match the authenticated mailbox.
    }



    public function filter_mail_from(string $from): string
    {
        if ((int) $this->settings->get('smtp_enabled', 0) !== 1 || $this->external_mail_plugins->has_external_delivery_plugin()) {
            return $from;
        }

        $smtp_from = sanitize_email((string) $this->settings->get('smtp_sender_email', ''));
        if ($smtp_from !== '' && is_email($smtp_from)) {
            return $smtp_from;
        }

        $username = sanitize_email((string) $this->settings->get('smtp_username', ''));
        if ($username !== '' && is_email($username)) {
            return $username;
        }

        return $from;
    }

    public function filter_mail_from_name(string $name): string
    {
        if ((int) $this->settings->get('smtp_enabled', 0) !== 1 || $this->external_mail_plugins->has_external_delivery_plugin()) {
            return $name;
        }

        $smtp_name = sanitize_text_field((string) $this->settings->get('smtp_sender_name', ''));
        return $smtp_name !== '' ? $smtp_name : $name;
    }

    public function log_mail_failure(WP_Error $error): void
    {
        $settings = $this->settings->all();
        $payload = [
            'wp_error_code' => $error->get_error_code(),
            'wp_error_message' => $error->get_error_message(),
            'wp_error_data' => $error->get_error_data(),
            'smtp_enabled' => (int) ($settings['smtp_enabled'] ?? 0),
            'smtp_host' => (string) ($settings['smtp_host'] ?? ''),
            'smtp_port' => (int) ($settings['smtp_port'] ?? 0),
            'smtp_encryption' => (string) ($settings['smtp_encryption'] ?? ''),
            'smtp_auth' => (int) ($settings['smtp_auth'] ?? 0),
            'smtp_username_set' => ((string) ($settings['smtp_username'] ?? '') !== '') ? 1 : 0,
            'smtp_password_set' => ((string) ($settings['smtp_password'] ?? '') !== '') ? 1 : 0,
            'smtp_allow_insecure_ssl' => (int) ($settings['smtp_allow_insecure_ssl'] ?? 0),
            'smtp_timeout' => (int) ($settings['smtp_timeout'] ?? 0),
        ];

        (new ActivityLogRepository())->create([
            'object_type' => 'system',
            'object_id' => 0,
            'event' => 'smtp_mail_failed',
            'status' => 'error',
            'message' => 'WordPress/PHPMailer mail delivery failed.',
            'error_message' => $error->get_error_message(),
            'payload' => $payload,
        ]);
    }

}
