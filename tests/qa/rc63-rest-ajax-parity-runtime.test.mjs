import test from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { resolvePhpExecutable } from '../../tools/php-runtime.mjs';

const normalizer = 'includes/Application/Services/PublicBookingRequestNormalizer.php';
const ajaxController = readFileSync('includes/Frontend/Controllers/BookingController.php', 'utf8');
const restController = readFileSync('includes/Frontend/Controllers/RestApiController.php', 'utf8');
const phpExecutable = resolvePhpExecutable();

function runPhp(payloadA, payloadB) {
  const script = String.raw`
define('ABSPATH', __DIR__);
function absint($v){ return abs((int)$v); }
function sanitize_text_field($v){ return trim(strip_tags((string)$v)); }
function sanitize_email($v){ return filter_var((string)$v, FILTER_SANITIZE_EMAIL); }
function sanitize_key($v){ return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string)$v)); }
require ${JSON.stringify(process.cwd() + '/' + normalizer)};
$a = json_decode(base64_decode($argv[1]), true);
$b = json_decode(base64_decode($argv[2]), true);
$outA = \Slotera\Application\Services\PublicBookingRequestNormalizer::normalize($a, 'frontend');
$outB = \Slotera\Application\Services\PublicBookingRequestNormalizer::normalize($b, 'rest');
unset($outA['source'], $outB['source']);
echo json_encode(['a'=>$outA,'b'=>$outB], JSON_UNESCAPED_SLASHES);
`;
  const args = [
    '-r', script,
    Buffer.from(JSON.stringify(payloadA)).toString('base64'),
    Buffer.from(JSON.stringify(payloadB)).toString('base64'),
  ];
  const result = spawnSync(phpExecutable, args, { encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
  return JSON.parse(result.stdout);
}

test('RC63 AJAX and REST normalize the same booking semantics at runtime', () => {
  const ajax = {
    package_id: '14', name: ' Alice ', email: 'alice@example.com', phone: '+372 555',
    date: '2026-09-10', end_date: '2026-09-13', start: '15:00', end: '11:00',
    resource_id: '7', staff_id: '2', payment_method: 'paypal', payment_mode: 'prepay',
    payment_choice: 'deposit_payment', extra_ids: '4, 9 4', coupon_code: 'SUMMER25', marketing_consent: '1'
  };
  const rest = {
    package_id: 14, customer_name: ' Alice ', customer_email: 'alice@example.com', customer_phone: '+372 555',
    booking_date: '2026-09-10', end_date: '2026-09-13', start_time: '15:00', end_time: '11:00',
    resource_id: 7, staff_id: 2, payment_method: 'paypal', payment_mode: 'prepay',
    payment_choice: 'deposit_payment', extra_ids: [4, 9, 4], coupon_code: 'SUMMER25', marketing_consent: 1
  };
  const { a, b } = runPhp(ajax, rest);
  assert.deepEqual(a, b);
  assert.deepEqual(a.extra_ids, [4, 9]);
  assert.equal(a.end_date, '2026-09-13');
  assert.equal(a.coupon_code, 'SUMMER25');
  assert.equal(a.payment_choice, 'deposit_payment');
});

test('RC63 both transports use the canonical public booking normalizer', () => {
  assert.match(ajaxController, /PublicBookingRequestNormalizer::normalize\(\$payload, 'frontend'\)/);
  assert.match(restController, /PublicBookingRequestNormalizer::normalize\(\$request->get_params\(\), 'rest'\)/);
});
