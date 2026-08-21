<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Centralized rules for temporary mail attachments.
 *
 * Files created here are for wp_mail attachments only: unpredictable names,
 * stored outside the web root, and safely cleaned up after mail delivery.
 */
final class SecureAttachmentFileService
{
    public const CRON_HOOK = 'sltr_cleanup_secure_mail_attachments';
    private const DIR_NAME = 'slotera-secure-mail-attachments';
    private const MAX_TTL_SECONDS = DAY_IN_SECONDS;

    public function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [$this, 'cleanup_expired']);
    }

    public function create(string $extension, string $bytes, string $prefix = 'attachment'): string
    {
        $extension = strtolower(trim($extension, '. '));
        if (!in_array($extension, ['pdf', 'ics'], true) || $bytes === '') { return ''; }

        $dir = $this->directory();
        if ($dir === '') { return ''; }

        $prefix = sanitize_key($prefix);
        if ($prefix === '') { $prefix = 'attachment'; }

        try {
            $token = bin2hex(random_bytes(24));
        } catch (\Throwable $e) {
            $token = wp_generate_password(48, false, false);
        }

        $path = trailingslashit($dir) . $prefix . '-' . $token . '.' . $extension;
        if (file_put_contents($path, $bytes, LOCK_EX) === false) { return ''; }
        @chmod($path, 0600);
        return $path;
    }

    public function cleanup(array $files): void
    {
        $dir = $this->directory(false);
        if ($dir === '') { return; }
        $base = trailingslashit(wp_normalize_path($dir));
        foreach ($files as $file) {
            if (!is_string($file) || $file === '') { continue; }
            $path = wp_normalize_path($file);
            if (strpos($path, $base) !== 0 || !is_file($path)) { continue; }
            @unlink($path);
        }
    }

    public function cleanup_expired(): void
    {
        if (!CronResilienceService::acquire(self::CRON_HOOK, 10 * MINUTE_IN_SECONDS)) { return; }
        $deleted = 0;
        try {
            $dir = $this->directory(false);
            if ($dir === '' || !is_dir($dir)) {
                CronResilienceService::success(self::CRON_HOOK, ['deleted' => 0, 'reason' => 'directory_missing']);
                return;
            }
            $cutoff = time() - self::MAX_TTL_SECONDS;
            foreach (glob(trailingslashit($dir) . '*.{pdf,ics}', GLOB_BRACE) ?: [] as $file) {
                if (is_file($file) && filemtime($file) !== false && filemtime($file) < $cutoff) {
                    if (@unlink($file)) { $deleted++; }
                }
            }
            CronResilienceService::success(self::CRON_HOOK, ['deleted' => $deleted]);
        } catch (\Throwable $e) {
            CronResilienceService::failure(self::CRON_HOOK, $e);
            throw $e;
        }
    }

    private function directory(bool $create = true): string
    {
        $candidates = [];
        if (defined('WP_TEMP_DIR') && WP_TEMP_DIR) {
            $candidates[] = (string) WP_TEMP_DIR;
        }
        $candidates[] = sys_get_temp_dir();
        if (function_exists('get_temp_dir')) {
            $candidates[] = (string) get_temp_dir();
        }

        foreach (array_unique(array_filter($candidates)) as $base) {
            $base = rtrim(wp_normalize_path($base), '/');
            if ($base === '' || $this->is_public_path($base)) { continue; }
            if (!is_dir($base) || !is_writable($base)) { continue; }

            $dir = trailingslashit($base) . self::DIR_NAME;
            if ($create && !is_dir($dir) && !wp_mkdir_p($dir)) { continue; }
            if (!is_dir($dir) || !is_writable($dir) || $this->is_public_path($dir)) { continue; }
            @chmod($dir, 0700);
            return $dir;
        }

        // Fail closed: attachments containing customer/payment data must never
        // fall back to wp-content/uploads or another web-served directory.
        return '';
    }

    private function is_public_path(string $path): bool
    {
        $path = trailingslashit(wp_normalize_path($path));
        $roots = [];
        if (defined('ABSPATH') && ABSPATH) {
            $roots[] = trailingslashit(wp_normalize_path((string) ABSPATH));
        }
        $upload = wp_upload_dir(null, false);
        if (!empty($upload['basedir'])) {
            $roots[] = trailingslashit(wp_normalize_path((string) $upload['basedir']));
        }
        foreach ($roots as $root) {
            if ($root !== '/' && strpos($path, $root) === 0) { return true; }
        }
        return false;
    }


}
