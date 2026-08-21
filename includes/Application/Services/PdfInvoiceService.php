<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Application\Services\TranslationService;
use Slotera\Core\Events;

if (!defined('ABSPATH')) { exit; }

final class PdfInvoiceService
{
    private SettingsRepository $settings;
    private string $invoice_locale = 'en_US';

    public function __construct(?SettingsRepository $settings = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
    }

    public function is_enabled(): bool
    {
        return (int) $this->settings->get('invoice_pdf_enabled', 1) === 1;
    }

    public function filename(array $booking): string
    {
        $id = max(0, (int) ($booking['id'] ?? 0));
        return 'slotera-invoice-' . ($id > 0 ? (string) $id : 'booking') . '.pdf';
    }

    public function generate_file(array $booking, array $package = []): string
    {
        $bytes = $this->render_pdf_bytes($booking, $package);
        if ($bytes === '') { return ''; }

        $path = (new SecureAttachmentFileService())->create('pdf', $bytes, 'invoice');
        if ($path !== '') {
            Events::dispatch(Events::INVOICE_CREATED, [
                'booking_id' => (int) ($booking['id'] ?? 0),
                'booking' => $booking,
                'package' => $package,
                'invoice_path' => $path,
                'invoice_filename' => $this->filename($booking),
            ]);
        }
        return $path;
    }

    public function stream(array $booking, array $package = []): void
    {
        $bytes = $this->render_pdf_bytes($booking, $package);
        if ($bytes === '') {
            nocache_headers();
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><title>PDF engine unavailable</title>';
            echo '<div style="font-family:system-ui,-apple-system,Segoe UI,sans-serif;max-width:760px;margin:40px auto;padding:24px;border:1px solid #dcdcde;border-radius:8px;background:#fff;color:#111827">';
            echo '<h1 style="margin-top:0">' . esc_html__('PDF engine unavailable', 'slotera-booking') . '</h1>';
            echo '<p>' . esc_html__('The built-in PDF engine could not be initialized. This usually indicates an incomplete or corrupted plugin installation. Reinstall the plugin or deploy the complete production build.', 'slotera-booking') . '</p>';
            echo '<hr><div>' . $this->render_invoice_html($booking, $package, false) . '</div>';
            echo '</div>';
            return;
        }

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $this->filename($booking) . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
    }

    private function render_pdf_bytes(array $booking, array $package = []): string
    {
        $this->invoice_locale = $this->resolve_locale($booking);
        $data = $this->invoice_data($booking, $package);
        return $this->build_unicode_pdf($data);
    }

    /**
     * Kept for backward compatibility with the 1.0.549 dompdf transition.
     * The install ZIP now ships a self-contained Unicode PDF renderer, so customers do not need Composer.
     */
    private function load_dompdf(): bool
    {
        return true;
    }

    private function invoice_data(array $booking, array $package = []): array
    {
        $currency = CurrencyService::normalize((string) $this->settings->get('payment_currency', 'EUR'));
        $position = CurrencyService::normalize_position((string) $this->settings->get('payment_currency_position', 'right'));
        $total_value = (float) ($booking['gross_amount'] ?? $booking['total_amount'] ?? 0);
        $tax_value = (float) ($booking['tax_amount'] ?? 0);
        $subtotal_value = max(0, $total_value - $tax_value);
        $paid_value = (float) ($booking['paid_amount'] ?? 0);
        $due_value = (float) ($booking['remaining_amount'] ?? (($booking['total_amount'] ?? 0) - ($booking['paid_amount'] ?? 0)));

        $service = $this->localized_package_title($package, $booking);
        $display = $this->booking_display($booking, $package);
        $date = $display['date'];
        $time = $display['time'];
        $brand_name = (string) $this->settings->get('invoice_pdf_brand_name', get_bloginfo('name'));
        if ($brand_name === '') { $brand_name = get_bloginfo('name'); }
        $footer_text = trim((string) $this->settings->get('invoice_pdf_footer_text', ''));
        if ($footer_text === 'Thank you for your booking.') { $footer_text = ''; }
        if (sanitize_key((string) ($booking['payment_status'] ?? '')) === 'partial') {
            $balance_note = sltr__('frontend.remaining_balance_paid_on_site', $this->invoice_locale);
            $footer_text = trim($balance_note . ($footer_text !== '' ? "\n\n" . $footer_text : ''));
        }

        return [
            'title' => $this->invoice_label('Invoice'),
            'brand' => $brand_name,
            'invoice_number_label' => $this->invoice_label('Invoice #'),
            'invoice_number' => (string) (($booking['invoice_number'] ?? '') ?: ($booking['id'] ?? '')),
            'issued_label' => $this->invoice_label('Issued'),
            'issued' => sltr_format_localized_date(current_time('Y-m-d'), $this->invoice_locale),
            'accent' => '#2563eb',
            'text' => '#000000',
            'background' => '#ffffff',
            'sections' => [
                [
                    'title' => $this->invoice_label('Customer'),
                    'rows' => [
                        [$this->invoice_label('Name'), (string) ($booking['customer_name'] ?? '')],
                        [$this->invoice_label('Email'), (string) ($booking['customer_email'] ?? '')],
                        [$this->invoice_label('Phone'), (string) ($booking['customer_phone'] ?? '')],
                    ],
                ],
                [
                    'title' => $this->invoice_label('Booking'),
                    'rows' => [
                        [$this->invoice_label('Service'), $service],
                        ...($date !== '' ? [[$this->invoice_label('Date'), $date]] : []),
                        ...($time !== '' ? [[$this->invoice_label('Time'), $time]] : []),
                        [$this->invoice_label('Booking status'), $this->status_label((string) ($booking['status'] ?? ''))],
                        [$this->invoice_label('Payment status'), $this->status_label((string) ($booking['payment_status'] ?? ''))],
                    ],
                ],
                [
                    'title' => $this->invoice_label('Amounts'),
                    'rows' => array_values(array_filter([
                        [$this->invoice_label('Subtotal'), CurrencyService::format_for_locale($subtotal_value, $currency, $position, $this->invoice_locale)],
                        $tax_value > 0.000001 ? [$this->tax_label(), CurrencyService::format_for_locale($tax_value, $currency, $position, $this->invoice_locale)] : null,
                        [$this->invoice_label('Total'), CurrencyService::format_for_locale($total_value, $currency, $position, $this->invoice_locale)],
                        [$this->invoice_label('Paid'), CurrencyService::format_for_locale($paid_value, $currency, $position, $this->invoice_locale)],
                        [$this->invoice_label('Due'), CurrencyService::format_for_locale($due_value, $currency, $position, $this->invoice_locale)],
                    ], static fn ($row): bool => is_array($row))),
                ],
            ],
            'footer' => $footer_text,
        ];
    }

    private function build_unicode_pdf(array $data): string
    {
        $w = 595.0;
        $h = 842.0;
        $ops = [];
        $cid_by_font_char = [1 => []];
        $char_by_font_cid = [1 => []];
        $add_text = function (string $text) use (&$cid_by_font_char, &$char_by_font_cid): array {
            $text = $this->normalize_pdf_text($text);
            $segments = [];
            $font_slot = 0;
            foreach ($this->unicode_chars($text) as $ch) {
                $next_font_slot = 1;
                if (!isset($cid_by_font_char[$next_font_slot][$ch])) {
                    $cid = count($char_by_font_cid[$next_font_slot]) + 1;
                    if ($cid > 65535) { continue; }
                    $cid_by_font_char[$next_font_slot][$ch] = $cid;
                    $char_by_font_cid[$next_font_slot][$cid] = $ch;
                }
                if ($font_slot !== $next_font_slot) {
                    $segments[] = ['font' => $next_font_slot, 'bytes' => ''];
                    $font_slot = $next_font_slot;
                }
                $segments[count($segments) - 1]['bytes'] .= pack('n', $cid_by_font_char[$next_font_slot][$ch]);
            }
            return $segments;
        };
        $rgb = function (string $hex): array {
            $hex = ltrim($hex, '#');
            return [hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255];
        };
        $set_fill = function (array $color) use (&$ops): void {
            $ops[] = sprintf('%.4F %.4F %.4F rg', $color[0], $color[1], $color[2]);
        };
        $rect = function (float $x, float $y, float $rw, float $rh, array $color) use (&$ops, $set_fill): void {
            $set_fill($color);
            $ops[] = sprintf('%.2F %.2F %.2F %.2F re f', $x, $y, $rw, $rh);
        };
        $text = function (float $x, float $y, string $value, float $size, array $color) use (&$ops, $set_fill, $add_text): void {
            if ($value === '') { return; }
            $set_fill($color);
            $ops[] = 'BT';
            $ops[] = sprintf('%.2F %.2F Td', $x, $y);
            foreach ($add_text($value) as $segment) {
                $ops[] = sprintf('/F%d %.2F Tf', (int) $segment['font'], $size);
                $ops[] = '<' . strtoupper(bin2hex((string) $segment['bytes'])) . '> Tj';
            }
            $ops[] = 'ET';
        };

        $accent = $rgb((string) ($data['accent'] ?? '#2563eb'));
        $text_color = $rgb((string) ($data['text'] ?? '#000000'));
        $muted = [0.39, 0.45, 0.55];
        $light = [0.97, 0.98, 0.99];
        $border = [0.90, 0.92, 0.95];
        $bg = $rgb((string) ($data['background'] ?? '#ffffff'));

        $rect(0, 0, $w, $h, $bg);
        $rect(34, 720, 527, 88, $light);
        $rect(34, 720, 5, 88, $accent);
        $text(56, 780, (string) $data['title'], 24, $accent);
        $text(56, 754, (string) $data['brand'], 13, $text_color);
        $text(56, 735, (string) $data['invoice_number_label'] . ': ' . (string) $data['invoice_number'], 10.5, $text_color);
        $text(250, 735, (string) $data['issued_label'] . ': ' . (string) $data['issued'], 10.5, $text_color);

        $y = 680.0;
        foreach ((array) ($data['sections'] ?? []) as $section) {
            $rows = (array) ($section['rows'] ?? []);
            $wrapped = [];
            $section_height = 38.0;
            foreach ($rows as $row) {
                $label = (string) ($row[0] ?? '');
                $value = (string) ($row[1] ?? '');
                $lines = $this->wrap_pdf_text($value, 56);
                if ($lines === []) { $lines = ['']; }
                $wrapped[] = [$label, $lines];
                $section_height += max(18, count($lines) * 14);
            }
            if ($y - $section_height < 56) {
                // Keep MVP invoices one page where possible; if not, continue lower with safe spacing.
                $y = max($y, 120.0);
            }
            $rect(34, $y - $section_height + 12, 527, $section_height, [1, 1, 1]);
            // top/bottom light rules instead of rounded borders.
            $rect(34, $y + 10, 527, 0.8, $border);
            $rect(34, $y - $section_height + 12, 527, 0.8, $border);
            $text(52, $y - 12, (string) ($section['title'] ?? ''), 14, $accent);
            $row_y = $y - 34;
            foreach ($wrapped as $item) {
                $label = (string) $item[0];
                $lines = (array) $item[1];
                $text(56, $row_y, $label, 10.5, $text_color);
                $line_y = $row_y;
                foreach ($lines as $line) {
                    $text(210, $line_y, (string) $line, 10.5, $text_color);
                    $line_y -= 14;
                }
                $row_y -= max(18, count($lines) * 14);
            }
            $y -= $section_height + 15;
        }
        $text(52, max(42, $y - 6), (string) ($data['footer'] ?? ''), 10, $muted);

        $stream = implode("\n", $ops);
        $objects = [];
        $add = function (string $body) use (&$objects): int { $objects[] = $body; return count($objects); };
        $catalog = $add('');
        $pages = $add('');
        $font_resources = [];
        $font_configs = [
            1 => ['file' => 'NotoSans-Slotera.ttf', 'name' => 'NotoSans'],
        ];
        foreach ($font_configs as $slot => $config) {
            if ($char_by_font_cid[$slot] === []) { continue; }
            $font_data = $this->load_unicode_font($char_by_font_cid[$slot], $config['file']);
            if ($font_data === null) { return ''; }
            $to_unicode = $add($this->pdf_stream($this->to_unicode_cmap($char_by_font_cid[$slot])));
            $font_file = $add($this->pdf_font_stream($font_data['bytes']));
            $cid_to_gid = $add($this->pdf_stream($this->cid_to_gid_map($font_data['glyphs'])));
            $font_descriptor = $add('<< /Type /FontDescriptor /FontName /' . $config['name'] . ' /Flags 32 /FontBBox [' . implode(' ', $font_data['bbox']) . '] /ItalicAngle 0 /Ascent ' . $font_data['ascent'] . ' /Descent ' . $font_data['descent'] . ' /CapHeight ' . $font_data['cap_height'] . ' /StemV 80 /FontFile2 ' . $font_file . ' 0 R >>');
            $cid_widths = $this->cid_widths($font_data['glyphs'], $font_data['widths'], $font_data['units_per_em']);
            $cid_font = $add('<< /Type /Font /Subtype /CIDFontType2 /BaseFont /' . $config['name'] . ' /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor ' . $font_descriptor . ' 0 R /CIDToGIDMap ' . $cid_to_gid . ' 0 R /DW 1000 ' . ($cid_widths !== '' ? '/W ' . $cid_widths . ' ' : '') . '>>');
            $type0_font = $add('<< /Type /Font /Subtype /Type0 /BaseFont /' . $config['name'] . ' /Encoding /Identity-H /DescendantFonts [' . $cid_font . ' 0 R] /ToUnicode ' . $to_unicode . ' 0 R >>');
            $font_resources[] = '/F' . $slot . ' ' . $type0_font . ' 0 R';
        }
        $content = $add($this->pdf_stream($stream));
        $page = $add('<< /Type /Page /Parent ' . $pages . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << ' . implode(' ', $font_resources) . ' >> >> /Contents ' . $content . ' 0 R >>');
        $objects[$pages - 1] = '<< /Type /Pages /Kids [' . $page . ' 0 R] /Count 1 >>';
        $objects[$catalog - 1] = '<< /Type /Catalog /Pages ' . $pages . ' 0 R >>';

        return $this->assemble_pdf($objects, $catalog);
    }

    private function normalize_pdf_text(string $text): string
    {
        $text = preg_replace('/^\xEF\xBB\xBF/u', '', $text) ?: $text;
        $text = str_replace(["\r\n", "\r", "\n", "\t"], [' ', ' ', ' ', ' '], $text);
        $text = preg_replace('/\s{2,}/u', ' ', $text) ?: $text;
        return trim($text);
    }

    private function wrap_pdf_text(string $text, int $max_chars): array
    {
        $text = $this->normalize_pdf_text($text);
        if ($text === '') { return ['']; }
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $try = $line === '' ? $word : $line . ' ' . $word;
            if ($this->unicode_length($try) <= $max_chars) {
                $line = $try;
                continue;
            }
            if ($line !== '') { $lines[] = $line; }
            if ($this->unicode_length($word) > $max_chars) {
                foreach ($this->chunk_unicode($word, $max_chars) as $chunk) { $lines[] = $chunk; }
                $line = '';
            } else {
                $line = $word;
            }
        }
        if ($line !== '') { $lines[] = $line; }
        return $lines;
    }

    private function unicode_chars(string $text): array
    {
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function unicode_length(string $text): int
    {
        return count($this->unicode_chars($text));
    }

    private function chunk_unicode(string $text, int $size): array
    {
        $chars = $this->unicode_chars($text);
        $chunks = [];
        for ($i = 0; $i < count($chars); $i += $size) {
            $chunks[] = implode('', array_slice($chars, $i, $size));
        }
        return $chunks;
    }

    private function cid_widths(array $glyphs, array $widths, int $units_per_em): string
    {
        if ($glyphs === [] || $units_per_em <= 0) { return ''; }
        $parts = [];
        foreach ($glyphs as $cid => $glyph) {
            $advance = (int) ($widths[$glyph] ?? $widths[0] ?? $units_per_em);
            $parts[] = (string) $cid . ' [' . (string) max(1, (int) round(($advance * 1000) / $units_per_em)) . ']';
        }
        return '[ ' . implode(' ', $parts) . ' ]';
    }

    private function unicode_codepoint(string $ch): ?int
    {
        $utf32 = @iconv('UTF-8', 'UTF-32BE//IGNORE', $ch);
        if (!is_string($utf32) || strlen($utf32) !== 4) { return null; }
        $unpacked = unpack('N', $utf32);
        return isset($unpacked[1]) ? (int) $unpacked[1] : null;
    }


    private function to_unicode_cmap(array $char_by_cid): string
    {
        $pairs = [];
        foreach ($char_by_cid as $cid => $ch) {
            $utf16 = (string) @iconv('UTF-8', 'UTF-16BE//IGNORE', (string) $ch);
            if ($utf16 === '') { continue; }
            $pairs[] = '<' . sprintf('%04X', (int) $cid) . '> <' . strtoupper(bin2hex($utf16)) . '>';
        }
        if ($pairs === []) { $pairs[] = '<0020> <0020>'; }
        return "/CIDInit /ProcSet findresource begin\n" .
            "12 dict begin\n" .
            "begincmap\n" .
            "/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n" .
            "/CMapName /Adobe-Identity-UCS def\n" .
            "/CMapType 2 def\n" .
            "1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n" .
            count($pairs) . " beginbfchar\n" . implode("\n", $pairs) . "\nendbfchar\n" .
            "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
    }

    /**
     * Loads the bundled OFL Unicode font and resolves the document's compact
     * CIDs to its actual TrueType glyph IDs. Embedding the font plus this map
     * makes the PDF independent from fonts installed on the reader's device.
     */
    private function load_unicode_font(array $char_by_cid, string $filename): ?array
    {
        static $fonts = [];
        static $failed = [];
        if (!empty($failed[$filename])) { return null; }

        if (!isset($fonts[$filename])) {
            $path = SLTR_PLUGIN_DIR . 'assets/fonts/' . $filename;
            $bytes = is_readable($path) ? file_get_contents($path) : false;
            if (!is_string($bytes) || strlen($bytes) < 12) {
                $failed[$filename] = true;
                return null;
            }

            $num_tables = $this->ttf_u16($bytes, 4);
            if ($num_tables === null) { $failed[$filename] = true; return null; }
            $tables = [];
            for ($i = 0; $i < $num_tables; $i++) {
                $record = 12 + ($i * 16);
                if ($record + 16 > strlen($bytes)) { $failed[$filename] = true; return null; }
                $tag = substr($bytes, $record, 4);
                $offset = $this->ttf_u32($bytes, $record + 8);
                $length = $this->ttf_u32($bytes, $record + 12);
                if ($offset === null || $length === null || $offset + $length > strlen($bytes)) {
                    $failed[$filename] = true;
                    return null;
                }
                $tables[$tag] = ['offset' => $offset, 'length' => $length];
            }

            foreach (['head', 'hhea', 'maxp', 'hmtx', 'cmap'] as $required) {
                if (!isset($tables[$required])) { $failed[$filename] = true; return null; }
            }
            $head = $tables['head']['offset'];
            $hhea = $tables['hhea']['offset'];
            $maxp = $tables['maxp']['offset'];
            $units = $this->ttf_u16($bytes, $head + 18);
            $glyph_count = $this->ttf_u16($bytes, $maxp + 4);
            $metric_count = $this->ttf_u16($bytes, $hhea + 34);
            if (!$units || !$glyph_count || !$metric_count || $metric_count > $glyph_count) {
                $failed[$filename] = true;
                return null;
            }

            $widths = [];
            $hmtx = $tables['hmtx']['offset'];
            $last_advance = $units;
            for ($glyph = 0; $glyph < $glyph_count; $glyph++) {
                if ($glyph < $metric_count) {
                    $advance = $this->ttf_u16($bytes, $hmtx + ($glyph * 4));
                    if ($advance === null) { $failed[$filename] = true; return null; }
                    $last_advance = $advance;
                }
                $widths[$glyph] = $last_advance;
            }

            $cmap = $this->select_ttf_cmap($bytes, $tables['cmap']['offset']);
            if ($cmap === null) { $failed[$filename] = true; return null; }
            $scale = static fn (int $value): int => (int) round(($value * 1000) / $units);
            $x_min = $this->ttf_i16($bytes, $head + 36);
            $y_min = $this->ttf_i16($bytes, $head + 38);
            $x_max = $this->ttf_i16($bytes, $head + 40);
            $y_max = $this->ttf_i16($bytes, $head + 42);
            $ascent = $this->ttf_i16($bytes, $hhea + 4);
            $descent = $this->ttf_i16($bytes, $hhea + 6);
            if ($x_min === null || $y_min === null || $x_max === null || $y_max === null || $ascent === null || $descent === null) {
                $failed[$filename] = true;
                return null;
            }

            $fonts[$filename] = [
                'bytes' => $bytes,
                'cmap' => $cmap,
                'widths' => $widths,
                'units_per_em' => $units,
                'bbox' => [$scale($x_min), $scale($y_min), $scale($x_max), $scale($y_max)],
                'ascent' => $scale($ascent),
                'descent' => $scale($descent),
                'cap_height' => $scale($ascent),
            ];
        }

        $glyphs = [];
        foreach ($char_by_cid as $cid => $char) {
            $codepoint = $this->unicode_codepoint((string) $char);
            $glyphs[(int) $cid] = $codepoint === null ? 0 : $this->ttf_glyph($fonts[$filename]['bytes'], $fonts[$filename]['cmap'], $codepoint);
        }
        return $fonts[$filename] + ['glyphs' => $glyphs];
    }

    private function select_ttf_cmap(string $bytes, int $offset): ?array
    {
        $count = $this->ttf_u16($bytes, $offset + 2);
        if ($count === null) { return null; }
        $best = null;
        $best_score = -1;
        for ($i = 0; $i < $count; $i++) {
            $record = $offset + 4 + ($i * 8);
            $platform = $this->ttf_u16($bytes, $record);
            $encoding = $this->ttf_u16($bytes, $record + 2);
            $relative = $this->ttf_u32($bytes, $record + 4);
            if ($platform === null || $encoding === null || $relative === null) { continue; }
            $subtable = $offset + $relative;
            $format = $this->ttf_u16($bytes, $subtable);
            if (!in_array($format, [4, 12], true)) { continue; }
            $score = ($format === 12 ? 100 : 0) + ($platform === 3 ? 20 : 10) + ($encoding === 10 ? 5 : 0);
            if ($score > $best_score) {
                $best_score = $score;
                $best = ['offset' => $subtable, 'format' => $format];
            }
        }
        return $best;
    }

    private function ttf_glyph(string $bytes, array $cmap, int $codepoint): int
    {
        $offset = (int) $cmap['offset'];
        if ((int) $cmap['format'] === 12) {
            $groups = $this->ttf_u32($bytes, $offset + 12) ?? 0;
            $low = 0;
            $high = $groups - 1;
            while ($low <= $high) {
                $middle = (int) floor(($low + $high) / 2);
                $group = $offset + 16 + ($middle * 12);
                $start = $this->ttf_u32($bytes, $group) ?? 0;
                $end = $this->ttf_u32($bytes, $group + 4) ?? -1;
                if ($codepoint < $start) { $high = $middle - 1; continue; }
                if ($codepoint > $end) { $low = $middle + 1; continue; }
                $start_glyph = $this->ttf_u32($bytes, $group + 8) ?? 0;
                return (int) ($start_glyph + $codepoint - $start);
            }
            return 0;
        }

        if ($codepoint > 0xFFFF) { return 0; }
        $segments = (int) (($this->ttf_u16($bytes, $offset + 6) ?? 0) / 2);
        $end_codes = $offset + 14;
        $start_codes = $end_codes + ($segments * 2) + 2;
        $deltas = $start_codes + ($segments * 2);
        $range_offsets = $deltas + ($segments * 2);
        for ($i = 0; $i < $segments; $i++) {
            $end = $this->ttf_u16($bytes, $end_codes + ($i * 2)) ?? -1;
            if ($codepoint > $end) { continue; }
            $start = $this->ttf_u16($bytes, $start_codes + ($i * 2)) ?? 0;
            if ($codepoint < $start) { return 0; }
            $delta = $this->ttf_i16($bytes, $deltas + ($i * 2)) ?? 0;
            $range = $this->ttf_u16($bytes, $range_offsets + ($i * 2)) ?? 0;
            if ($range === 0) { return ($codepoint + $delta) & 0xFFFF; }
            $glyph_offset = $range_offsets + ($i * 2) + $range + (($codepoint - $start) * 2);
            $glyph = $this->ttf_u16($bytes, $glyph_offset) ?? 0;
            return $glyph === 0 ? 0 : (($glyph + $delta) & 0xFFFF);
        }
        return 0;
    }

    private function cid_to_gid_map(array $glyphs): string
    {
        if ($glyphs === []) { return pack('n', 0); }
        $map = pack('n', 0);
        $max = max(array_keys($glyphs));
        for ($cid = 1; $cid <= $max; $cid++) {
            $map .= pack('n', max(0, min(65535, (int) ($glyphs[$cid] ?? 0))));
        }
        return $map;
    }

    private function ttf_u16(string $bytes, int $offset): ?int
    {
        if ($offset < 0 || $offset + 2 > strlen($bytes)) { return null; }
        $value = unpack('nvalue', substr($bytes, $offset, 2));
        return isset($value['value']) ? (int) $value['value'] : null;
    }

    private function ttf_i16(string $bytes, int $offset): ?int
    {
        $value = $this->ttf_u16($bytes, $offset);
        return $value === null ? null : ($value >= 0x8000 ? $value - 0x10000 : $value);
    }

    private function ttf_u32(string $bytes, int $offset): ?int
    {
        if ($offset < 0 || $offset + 4 > strlen($bytes)) { return null; }
        $value = unpack('Nvalue', substr($bytes, $offset, 4));
        return isset($value['value']) ? (int) $value['value'] : null;
    }

    private function pdf_font_stream(string $font_bytes): string
    {
        static $streams = [];
        $length = strlen($font_bytes);
        $cache_key = (string) $length;
        if (isset($streams[$cache_key])) { return $streams[$cache_key]; }
        if (function_exists('gzcompress')) {
            $compressed = gzcompress($font_bytes, 9);
            if (is_string($compressed) && strlen($compressed) < $length) {
                return $streams[$cache_key] = '<< /Length ' . strlen($compressed) . ' /Length1 ' . $length . " /Filter /FlateDecode >>\nstream\n" . $compressed . "\nendstream";
            }
        }
        return $streams[$cache_key] = '<< /Length ' . $length . ' /Length1 ' . $length . ">>\nstream\n" . $font_bytes . "\nendstream";
    }

    private function pdf_stream(string $stream): string
    {
        return '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
    }

    private function assemble_pdf(array $objects, int $catalog_object): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ((string) ($index + 1)) . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root ' . $catalog_object . " 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function render_invoice_html(array $booking, array $package = [], bool $for_pdf = true): string
    {
        $this->invoice_locale = $this->resolve_locale($booking);
        $currency = CurrencyService::normalize((string) $this->settings->get('payment_currency', 'EUR'));
        $position = CurrencyService::normalize_position((string) $this->settings->get('payment_currency_position', 'right'));
        $total_value = (float) ($booking['gross_amount'] ?? $booking['total_amount'] ?? 0);
        $tax_value = (float) ($booking['tax_amount'] ?? 0);
        $subtotal_value = max(0, $total_value - $tax_value);
        $paid_value = (float) ($booking['paid_amount'] ?? 0);
        $due_value = (float) ($booking['remaining_amount'] ?? (($booking['total_amount'] ?? 0) - ($booking['paid_amount'] ?? 0)));

        $amounts = [
            $this->invoice_label('Subtotal') => CurrencyService::format_for_locale($subtotal_value, $currency, $position, $this->invoice_locale),
        ];
        if ($tax_value > 0.000001) {
            $amounts[$this->tax_label()] = CurrencyService::format_for_locale($tax_value, $currency, $position, $this->invoice_locale);
        }
        $amounts[$this->invoice_label('Total')] = CurrencyService::format_for_locale($total_value, $currency, $position, $this->invoice_locale);
        $amounts[$this->invoice_label('Paid')] = CurrencyService::format_for_locale($paid_value, $currency, $position, $this->invoice_locale);
        $amounts[$this->invoice_label('Due')] = CurrencyService::format_for_locale($due_value, $currency, $position, $this->invoice_locale);

        $service = $this->localized_package_title($package, $booking);
        $display = $this->booking_display($booking, $package);
        $date = $display['date'];
        $time = $display['time'];
        $brand_name = (string) $this->settings->get('invoice_pdf_brand_name', get_bloginfo('name'));
        if ($brand_name === '') { $brand_name = get_bloginfo('name'); }
        $footer_text = trim((string) $this->settings->get('invoice_pdf_footer_text', ''));
        if ($footer_text === 'Thank you for your booking.') { $footer_text = ''; }

        $accent = '#2563eb';
        $text = '#000000';
        $background = '#ffffff';

        $customer_rows = [
            $this->invoice_label('Name') => (string) ($booking['customer_name'] ?? ''),
            $this->invoice_label('Email') => (string) ($booking['customer_email'] ?? ''),
            $this->invoice_label('Phone') => (string) ($booking['customer_phone'] ?? ''),
        ];
        $booking_rows = [
            $this->invoice_label('Service') => $service,
            ...($date !== '' ? [$this->invoice_label('Date') => $date] : []),
            ...($time !== '' ? [$this->invoice_label('Time') => $time] : []),
            $this->invoice_label('Booking status') => $this->status_label((string) ($booking['status'] ?? '')),
            $this->invoice_label('Payment status') => $this->status_label((string) ($booking['payment_status'] ?? '')),
        ];

        ob_start();
        ?>
<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
@page { margin: 34px; }
* { box-sizing: border-box; }
body {
    margin: 0;
    background: <?php echo esc_html($background); ?>;
    color: <?php echo esc_html($text); ?>;
    font-family: "DejaVu Sans", "Noto Sans", Arial, sans-serif;
    font-size: 12px;
    line-height: 1.45;
}
.sltr-invoice { width: 100%; }
.sltr-header {
    padding: 22px 24px;
    border-radius: 12px;
    background: #f8fafc;
    border-left: 5px solid <?php echo esc_html($accent); ?>;
    margin-bottom: 22px;
}
.sltr-title { font-size: 28px; line-height: 1.1; margin: 0 0 10px; color: <?php echo esc_html($accent); ?>; font-weight: 700; }
.sltr-brand { font-size: 16px; font-weight: 700; margin-bottom: 12px; }
.sltr-meta { color: <?php echo esc_html($text); ?>; }
.sltr-section { margin: 0 0 18px; padding: 16px 18px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; page-break-inside: avoid; }
.sltr-section h2 { margin: 0 0 11px; color: <?php echo esc_html($accent); ?>; font-size: 16px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { padding: 6px 0; vertical-align: top; color: <?php echo esc_html($text); ?>; }
th { width: 34%; text-align: left; font-weight: 700; padding-right: 14px; white-space: normal; }
td { width: 66%; word-wrap: break-word; overflow-wrap: break-word; }
.sltr-amounts th, .sltr-amounts td { border-bottom: 1px solid #eef2f7; }
.sltr-amounts tr:last-child th, .sltr-amounts tr:last-child td { border-bottom: 0; font-weight: 700; }
.sltr-footer { margin-top: 22px; color: #64748b; font-size: 11px; }
</style>
</head>
<body>
<div class="sltr-invoice">
    <div class="sltr-header">
        <h1 class="sltr-title"><?php echo esc_html($this->invoice_label('Invoice')); ?></h1>
        <div class="sltr-brand"><?php echo esc_html($brand_name); ?></div>
        <div class="sltr-meta">
            <div><?php echo esc_html($this->invoice_label('Invoice #')); ?>: <?php echo esc_html((string) (($booking['invoice_number'] ?? '') ?: ($booking['id'] ?? ''))); ?></div>
            <div><?php echo esc_html($this->invoice_label('Issued')); ?>: <?php echo esc_html(sltr_format_localized_date(current_time('Y-m-d'), $this->invoice_locale)); ?></div>
        </div>
    </div>

    <?php echo $this->section_html($this->invoice_label('Customer'), $customer_rows); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php echo $this->section_html($this->invoice_label('Booking'), $booking_rows); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php echo $this->section_html($this->invoice_label('Amounts'), $amounts, 'sltr-amounts'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <?php if ($footer_text !== ''): ?>
    <div class="sltr-footer"><?php echo esc_html($footer_text); ?></div>
    <?php endif; ?>
</div>
</body>
</html>
        <?php
        return trim((string) ob_get_clean());
    }

    private function section_html(string $title, array $rows, string $table_class = ''): string
    {
        ob_start();
        ?>
        <div class="sltr-section">
            <h2><?php echo esc_html($title); ?></h2>
            <table class="<?php echo esc_attr($table_class); ?>"><tbody>
                <?php foreach ($rows as $label => $value) : ?>
                    <tr><th><?php echo esc_html((string) $label); ?></th><td><?php echo esc_html((string) $value); ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function invoice_label(string $label): string
    {
        $translated = sltr_t($label, 'frontend', $this->invoice_locale);
        if ($translated !== '' && $translated !== $label) {
            return $translated;
        }

        // PDF-only labels must never depend on the administrator locale.
        // Prefer an exact locale and then a language fallback.
        $locale = str_replace('-', '_', $this->invoice_locale);
        $language = strtolower(substr($locale, 0, 2));
        $pdf_labels = [
            'bg_BG' => ['Invoice #' => 'Фактура №', 'Issued' => 'Издадена', 'Booking' => 'Резервация', 'Booking status' => 'Статус на резервацията', 'Payment status' => 'Статус на плащането', 'Amounts' => 'Суми', 'Subtotal' => 'Междинна сума', 'Due' => 'За плащане'],
            'cs_CZ' => ['Invoice #' => 'Faktura č.', 'Issued' => 'Vystaveno', 'Booking' => 'Rezervace', 'Booking status' => 'Stav rezervace', 'Payment status' => 'Stav platby', 'Amounts' => 'Částky', 'Subtotal' => 'Mezisoučet', 'Due' => 'K úhradě'],
            'da_DK' => ['Invoice #' => 'Fakturanr.', 'Issued' => 'Udstedt', 'Booking' => 'Booking', 'Booking status' => 'Bookingstatus', 'Payment status' => 'Betalingsstatus', 'Amounts' => 'Beløb', 'Subtotal' => 'Subtotal', 'Due' => 'Til betaling'],
            'de_DE' => ['Invoice #' => 'Rechnungsnr.', 'Issued' => 'Ausgestellt', 'Booking' => 'Buchung', 'Booking status' => 'Buchungsstatus', 'Payment status' => 'Zahlungsstatus', 'Amounts' => 'Beträge', 'Subtotal' => 'Zwischensumme', 'Due' => 'Fällig'],
            'el_GR' => ['Invoice #' => 'Τιμολόγιο αρ.', 'Issued' => 'Εκδόθηκε', 'Booking' => 'Κράτηση', 'Booking status' => 'Κατάσταση κράτησης', 'Payment status' => 'Κατάσταση πληρωμής', 'Amounts' => 'Ποσά', 'Subtotal' => 'Μερικό σύνολο', 'Due' => 'Πληρωτέο'],
            'es_ES' => ['Invoice #' => 'Factura n.º', 'Issued' => 'Emitida', 'Booking' => 'Reserva', 'Booking status' => 'Estado de la reserva', 'Payment status' => 'Estado del pago', 'Amounts' => 'Importes', 'Subtotal' => 'Subtotal', 'Due' => 'Pendiente'],
            'et_EE' => ['Invoice #' => 'Arve nr', 'Issued' => 'Väljastatud', 'Booking' => 'Broneering', 'Booking status' => 'Broneeringu olek', 'Payment status' => 'Makse olek', 'Amounts' => 'Summad', 'Subtotal' => 'Vahesumma', 'Due' => 'Tasuda'],
            'fi_FI' => ['Invoice #' => 'Lasku nro', 'Issued' => 'Laadittu', 'Booking' => 'Varaus', 'Booking status' => 'Varauksen tila', 'Payment status' => 'Maksun tila', 'Amounts' => 'Summat', 'Subtotal' => 'Välisummaa', 'Due' => 'Maksettavaa'],
            'fr_FR' => ['Invoice #' => 'Facture n°', 'Issued' => 'Émise', 'Booking' => 'Réservation', 'Booking status' => 'Statut de la réservation', 'Payment status' => 'Statut du paiement', 'Amounts' => 'Montants', 'Subtotal' => 'Sous-total', 'Due' => 'À payer'],
            'ga_IE' => ['Invoice #' => 'Sonrasc uimh.', 'Issued' => 'Eisithe', 'Booking' => 'Áirithint', 'Booking status' => 'Stádas áirithinte', 'Payment status' => 'Stádas íocaíochta', 'Amounts' => 'Suimeanna', 'Subtotal' => 'Fo-iomlán', 'Due' => 'Le híoc'],
            'hr_HR' => ['Invoice #' => 'Račun br.', 'Issued' => 'Izdano', 'Booking' => 'Rezervacija', 'Booking status' => 'Status rezervacije', 'Payment status' => 'Status plaćanja', 'Amounts' => 'Iznosi', 'Subtotal' => 'Međuzbroj', 'Due' => 'Za platiti'],
            'hu_HU' => ['Invoice #' => 'Számla sorszáma', 'Issued' => 'Kiállítva', 'Booking' => 'Foglalás', 'Booking status' => 'Foglalás állapota', 'Payment status' => 'Fizetés állapota', 'Amounts' => 'Összegek', 'Subtotal' => 'Részösszeg', 'Due' => 'Fizetendő'],
            'is_IS' => ['Invoice #' => 'Reikningur nr.', 'Issued' => 'Gefinn út', 'Booking' => 'Bókun', 'Booking status' => 'Staða bókunar', 'Payment status' => 'Greiðslustaða', 'Amounts' => 'Upphæðir', 'Subtotal' => 'Millisamtala', 'Due' => 'Til greiðslu'],
            'it_IT' => ['Invoice #' => 'Fattura n.', 'Issued' => 'Emessa', 'Booking' => 'Prenotazione', 'Booking status' => 'Stato della prenotazione', 'Payment status' => 'Stato del pagamento', 'Amounts' => 'Importi', 'Subtotal' => 'Subtotale', 'Due' => 'Da pagare'],
            'lt_LT' => ['Invoice #' => 'Sąskaita Nr.', 'Issued' => 'Išrašyta', 'Booking' => 'Užsakymas', 'Booking status' => 'Užsakymo būsena', 'Payment status' => 'Mokėjimo būsena', 'Amounts' => 'Sumos', 'Subtotal' => 'Tarpinė suma', 'Due' => 'Mokėtina'],
            'lv_LV' => ['Invoice #' => 'Rēķins Nr.', 'Issued' => 'Izrakstīts', 'Booking' => 'Rezervācija', 'Booking status' => 'Rezervācijas statuss', 'Payment status' => 'Maksājuma statuss', 'Amounts' => 'Summas', 'Subtotal' => 'Starpsumma', 'Due' => 'Apmaksai'],
            'mt_MT' => ['Invoice #' => 'Fattura Nru.', 'Issued' => 'Maħruġa', 'Booking' => 'Prenotazzjoni', 'Booking status' => 'Status tal-prenotazzjoni', 'Payment status' => 'Status tal-ħlas', 'Amounts' => 'Ammonti', 'Subtotal' => 'Subtotal', 'Due' => 'Bilanċ dovut'],
            'nl_NL' => ['Invoice #' => 'Factuurnr.', 'Issued' => 'Uitgegeven', 'Booking' => 'Boeking', 'Booking status' => 'Boekingsstatus', 'Payment status' => 'Betalingsstatus', 'Amounts' => 'Bedragen', 'Subtotal' => 'Subtotaal', 'Due' => 'Te betalen'],
            'no_NO' => ['Invoice #' => 'Fakturanr.', 'Issued' => 'Utstedt', 'Booking' => 'Bestilling', 'Booking status' => 'Bestillingsstatus', 'Payment status' => 'Betalingsstatus', 'Amounts' => 'Beløp', 'Subtotal' => 'Delsum', 'Due' => 'Til betaling'],
            'nb_NO' => ['Invoice #' => 'Fakturanr.', 'Issued' => 'Utstedt', 'Booking' => 'Bestilling', 'Booking status' => 'Bestillingsstatus', 'Payment status' => 'Betalingsstatus', 'Amounts' => 'Beløp', 'Subtotal' => 'Delsum', 'Due' => 'Til betaling'],
            'pl_PL' => ['Invoice #' => 'Faktura nr', 'Issued' => 'Wystawiono', 'Booking' => 'Rezerwacja', 'Booking status' => 'Status rezerwacji', 'Payment status' => 'Status płatności', 'Amounts' => 'Kwoty', 'Subtotal' => 'Suma częściowa', 'Due' => 'Do zapłaty'],
            'pt_BR' => ['Invoice #' => 'Fatura nº', 'Issued' => 'Emitida', 'Booking' => 'Reserva', 'Booking status' => 'Status da reserva', 'Payment status' => 'Status do pagamento', 'Amounts' => 'Valores', 'Subtotal' => 'Subtotal', 'Due' => 'A pagar'],
            'pt_PT' => ['Invoice #' => 'Fatura n.º', 'Issued' => 'Emitida', 'Booking' => 'Reserva', 'Booking status' => 'Estado da reserva', 'Payment status' => 'Estado do pagamento', 'Amounts' => 'Montantes', 'Subtotal' => 'Subtotal', 'Due' => 'A pagar'],
            'ro_RO' => ['Invoice #' => 'Factură nr.', 'Issued' => 'Emisă', 'Booking' => 'Rezervare', 'Booking status' => 'Starea rezervării', 'Payment status' => 'Starea plății', 'Amounts' => 'Sume', 'Subtotal' => 'Subtotal', 'Due' => 'De plată'],
            'ru_RU' => ['Invoice #' => 'Счёт №', 'Issued' => 'Выдан', 'Booking' => 'Бронирование', 'Booking status' => 'Статус бронирования', 'Payment status' => 'Статус оплаты', 'Amounts' => 'Суммы', 'Subtotal' => 'Подытог', 'Due' => 'К оплате'],
            'sk_SK' => ['Invoice #' => 'Faktúra č.', 'Issued' => 'Vystavené', 'Booking' => 'Rezervácia', 'Booking status' => 'Stav rezervácie', 'Payment status' => 'Stav platby', 'Amounts' => 'Sumy', 'Subtotal' => 'Medzisúčet', 'Due' => 'Na úhradu'],
            'sl_SI' => ['Invoice #' => 'Račun št.', 'Issued' => 'Izdano', 'Booking' => 'Rezervacija', 'Booking status' => 'Stanje rezervacije', 'Payment status' => 'Stanje plačila', 'Amounts' => 'Zneski', 'Subtotal' => 'Vmesni seštevek', 'Due' => 'Za plačilo'],
            'sv_SE' => ['Invoice #' => 'Fakturanr.', 'Issued' => 'Utfärdad', 'Booking' => 'Bokning', 'Booking status' => 'Bokningsstatus', 'Payment status' => 'Betalningsstatus', 'Amounts' => 'Belopp', 'Subtotal' => 'Delsumma', 'Due' => 'Att betala'],
        ];
        $language_fallbacks = [
            'bg' => 'bg_BG', 'cs' => 'cs_CZ', 'da' => 'da_DK', 'de' => 'de_DE', 'el' => 'el_GR',
            'es' => 'es_ES', 'et' => 'et_EE', 'fi' => 'fi_FI', 'fr' => 'fr_FR', 'ga' => 'ga_IE',
            'hr' => 'hr_HR', 'hu' => 'hu_HU', 'is' => 'is_IS', 'it' => 'it_IT', 'lt' => 'lt_LT',
            'lv' => 'lv_LV', 'mt' => 'mt_MT', 'nl' => 'nl_NL', 'no' => 'no_NO', 'nb' => 'nb_NO',
            'pl' => 'pl_PL', 'pt' => 'pt_PT', 'ro' => 'ro_RO', 'ru' => 'ru_RU', 'sk' => 'sk_SK',
            'sl' => 'sl_SI', 'sv' => 'sv_SE',
        ];
        $resolved_locale = isset($pdf_labels[$locale]) ? $locale : ($language_fallbacks[$language] ?? '');
        if ($resolved_locale !== '' && isset($pdf_labels[$resolved_locale][$label])) {
            return $pdf_labels[$resolved_locale][$label];
        }

        return $label;
    }

    private function tax_label(): string
    {
        $tax_label = (string) $this->settings->get('payment_tax_label', __('Tax/VAT', 'slotera-booking'));
        return $tax_label !== '' ? $tax_label : __('Tax/VAT', 'slotera-booking');
    }

    private function status_label(string $status): string
    {
        $status = sanitize_key(trim($status));
        if ($status === '') { return ''; }
        $labels = [
            'confirmed' => 'Confirmed', 'pending' => 'Pending', 'pending_payment' => 'Pending payment',
            'unpaid' => 'Unpaid', 'paid' => 'Paid', 'partial' => 'Partially paid',
            'partially_paid' => 'Partially paid', 'failed' => 'Failed', 'cancelled' => 'Cancelled',
            'completed' => 'Completed', 'refunded' => 'Refunded', 'partially_refunded' => 'Partially refunded',
        ];
        return isset($labels[$status]) ? sltr_t($labels[$status], 'frontend', $this->invoice_locale) : ucwords(str_replace('_', ' ', $status));
    }

    private function resolve_locale(array $booking): string
    {
        $locale = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($booking['booking_locale'] ?? '')) ?: '';
        if ($locale === '' && class_exists('Slotera\Application\Services\TranslationService')) {
            $locale = (new TranslationService())->locale_for_group('frontend');
        }
        if ($locale === '') { $locale = function_exists('get_locale') ? (string) get_locale() : 'en_US'; }
        return str_replace('-', '_', $locale);
    }

    private function booking_display(array $booking, array $package): array
    {
        $mode = sanitize_key((string) ($booking['booking_mode'] ?? $package['booking_mode'] ?? 'simple'));
        if ($mode === 'flexible') { $mode = 'flex'; }
        if ($mode === 'simple') {
            // Simple / booking-request mode has no customer-selected date/time.
            // Persisted 00:00 sentinel values are internal state and must never
            // be rendered as invoice facts.
            return ['date' => '', 'time' => ''];
        }

        $configs = [];
        $raw_configs = $package['mode_configs_json'] ?? '';
        if (is_array($raw_configs)) {
            $configs = $raw_configs;
        } elseif (is_string($raw_configs) && trim($raw_configs) !== '') {
            $decoded = json_decode($raw_configs, true);
            if (is_array($decoded)) { $configs = $decoded; }
        }
        $active = isset($configs[$mode]) && is_array($configs[$mode]) ? $configs[$mode] : [];
        $date_only = $mode === 'fixed' && !empty($active['full_day_booking']);

        $start_raw = $this->normalize_booking_date((string) ($booking['booking_date'] ?? ''));
        $end_raw = $this->normalize_booking_date((string) ($booking['end_date'] ?? ''));
        $start = $start_raw !== '' ? sltr_format_localized_date($start_raw, $this->invoice_locale) : '';
        $end = $end_raw !== '' ? sltr_format_localized_date($end_raw, $this->invoice_locale) : '';
        $start_time = $date_only ? '' : substr((string) ($booking['start_time'] ?? ''), 0, 5);
        $end_time = $date_only ? '' : substr((string) ($booking['end_time'] ?? ''), 0, 5);
        $scheduled = !empty($booking['resource_id']) && $mode === 'date_range_inventory';
        $multi = $end_raw !== '' && $end_raw !== $start_raw;
        if ($scheduled && $multi) {
            return ['date' => trim($start . ($start_time !== '' ? ' ' . $start_time : '') . ' → ' . $end . ($end_time !== '' ? ' ' . $end_time : '')), 'time' => ''];
        }
        $time = $start_time;
        if ($end_time !== '' && $end_time !== $start_time) { $time .= ' – ' . $end_time; }
        return ['date' => $start . ($end !== '' && !$scheduled ? ' – ' . $end : ''), 'time' => $time];
    }

    private function normalize_booking_date(string $value): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^0{4}-0{2}-0{2}(?:[ T]0{2}:0{2}:0{2})?$/', $value)) {
            return '';
        }

        $date = substr($value, 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return '';
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year) ? $date : '';
    }

    private function localized_package_title(array $package, array $booking): string
    {
        $fallback = (string) ($package['title'] ?? ('Package #' . (string) ($booking['package_id'] ?? '')));
        $raw = (string) ($package['i18n_json'] ?? '');
        if ($raw === '') { return $fallback; }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) { return $fallback; }
        foreach ([$this->invoice_locale, substr($this->invoice_locale, 0, 2)] as $locale) {
            if (!empty($decoded[$locale]['title'])) { return (string) $decoded[$locale]['title']; }
        }
        return $fallback;
    }

    private function hex_color(string $color, string $fallback): string
    {
        $color = trim($color) !== '' ? trim($color) : $fallback;
        if (!preg_match('/^#?[a-fA-F0-9]{6}$/', $color)) { $color = $fallback; }
        return '#' . ltrim($color, '#');
    }
}
