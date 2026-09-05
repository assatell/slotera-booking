import test from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { resolvePhpExecutable } from '../../tools/php-runtime.mjs';

const phpExecutable = resolvePhpExecutable();

test('RC67 ICS preserves UTF-8 and fixed multi-day bookings as all-day ranges', () => {
  const customerName = '\u041d\u0410\u0422\u0410\u041b\u042c\u042f \u0418\u0421\u0422\u041e\u041c\u0418\u041d\u0410';
  const packageTitle = '\u041f\u0430\u043a\u0435\u0442 2';
  const dash = '\u2014';

  const php = String.raw`
namespace Slotera\Infrastructure\Repositories {
    class SettingsRepository {
        public function get($key, $default = null) {
            return match ($key) {
                'email_from_address' => 'booking@example.test',
                'email_from_name' => 'New Zone+ Application',
                default => $default,
            };
        }
    }
}

namespace Slotera\Application\Services {
    class EmailTemplateRegistry {
        public static function runtime_locale(): string {
            return 'ru_RU';
        }
    }
}

namespace {
    const ABSPATH = __DIR__;
    const HOUR_IN_SECONDS = 3600;

    function absint($v) { return abs((int) $v); }
    function wp_json_encode($v) { return json_encode($v, JSON_UNESCAPED_UNICODE); }
    function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
    function home_url() { return 'https://example.test'; }
    function sanitize_text_field($v) { return trim((string) $v); }
    function sanitize_key($v) {
        $v = strtolower((string) $v);
        return preg_replace('/[^a-z0-9_\-]/', '', $v);
    }
    function sanitize_email($v) { return trim((string) $v); }
    function is_email($v) { return filter_var($v, FILTER_VALIDATE_EMAIL) !== false; }
    function get_option($key) { return $key === 'admin_email' ? 'admin@example.test' : ''; }
    function get_bloginfo($key) { return 'Example'; }
    function wp_timezone() { return new \DateTimeZone('Europe/Tallinn'); }
    function __($text, $domain = null) { return $text; }

    function sltr_booking_display_data($booking, $package = []) {
        return ['date_only' => true];
    }

    require getcwd() . '/includes/Application/Services/CalendarInviteService.php';

    $service = new \Slotera\Application\Services\CalendarInviteService(
        new \Slotera\Infrastructure\Repositories\SettingsRepository()
    );

    $ics = $service->generate([
        'id' => 68,
        'booking_mode' => 'fixed',
        'booking_date' => '2026-08-24',
        'end_date' => '2026-08-26',
        'start_time' => '00:00:00',
        'end_time' => '00:00:00',
        'customer_name' => "\u{041d}\u{0410}\u{0422}\u{0410}\u{041b}\u{042c}\u{042f} \u{0418}\u{0421}\u{0422}\u{041e}\u{041c}\u{0418}\u{041d}\u{0410}",
        'customer_email' => 'customer@example.test',
        'customer_phone' => '+37255987662',
        'status' => 'confirmed',
        'payment_status' => 'unpaid',
    ], [
        'title' => "\u{041f}\u{0430}\u{043a}\u{0435}\u{0442} 2",
        'booking_mode' => 'fixed',
    ]);

    echo base64_encode($ics);
}
`;

  const result = spawnSync(phpExecutable, ['-r', php], {
    cwd: process.cwd(),
    encoding: 'utf8',
  });

  if (result.status !== 0) {
    console.error('PHP STATUS:', result.status);
    console.error('PHP ERROR:', result.error ?? '');
    console.error('PHP STDOUT:', result.stdout ?? '');
    console.error('PHP STDERR:', result.stderr ?? '');
  }
  assert.equal(
    result.status,
    0,
    `PHP failed. stdout=${result.stdout ?? ''} stderr=${result.stderr ?? ''}`
  );

  const ics = Buffer.from(result.stdout.trim(), 'base64').toString('utf8');
  const unfolded = ics.replace(/\r\n /g, '');

  assert.match(ics, /DTSTART;VALUE=DATE:20260824\r\n/);
  assert.match(ics, /DTEND;VALUE=DATE:20260826\r\n/);

  assert.ok(
    unfolded.includes(`SUMMARY:${packageTitle} ${dash} ${customerName}`),
    unfolded
  );

  assert.doesNotMatch(ics, /DTSTART:\d{8}T/);
  assert.doesNotMatch(ics, /DTEND:\d{8}T/);

  for (const line of ics.split('\r\n')) {
    assert.ok(
      Buffer.byteLength(line, 'utf8') <= 73,
      `ICS folded line exceeds 73 octets: ${line}`
    );
  }
});
