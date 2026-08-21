import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const root = process.cwd();

test('RC63.1 Simple quote executes production extras pricing with discounts and coupon', () => {
  const php = `<?php
  namespace {
    define('ABSPATH', __DIR__);
    function sanitize_text_field($v){ return trim((string)$v); }
    function sanitize_email($v){ return trim((string)$v); }
    function sanitize_key($v){ return strtolower(preg_replace('/[^a-z0-9_\\-]/i','',(string)$v)); }
    function absint($v){ return abs((int)$v); }
    function current_time($type){ return '2026-08-16'; }
    function is_wp_error($v){ return $v instanceof WP_Error; }
    class WP_Error {}
  }
  namespace Slotera\\Application\\Services {
    class PricingAdjustmentService {
      public function apply_dynamic(array $package, float $amount, array $context=[]): array { return ['dynamic_amount'=>round($amount*0.95,2),'dynamic_adjustment_amount'=>round($amount*-0.05,2),'dynamic_label'=>'Weekend']; }
      public function apply_tax(array $package, float $amount): array { return ['tax_amount'=>0,'total_amount'=>$amount]; }
    }
    class CouponService {
      public function package_sale_price(array $package): float { return (float)$package['price']; }
      public function validate_and_calculate(string $code, array $package, string $email): array { $base=(float)$package['price']; return ['valid'=>true,'coupon'=>['id'=>7,'code'=>$code],'discount_amount'=>round($base*0.10,2),'final_amount'=>round($base*0.90,2)]; }
    }
  }
  namespace { require '${path.join(root, 'includes/Application/Services/SimpleBookingQuoteService.php').replaceAll('\\','/')}';
    $svc = new \\Slotera\\Application\\Services\\SimpleBookingQuoteService(new \\Slotera\\Application\\Services\\PricingAdjustmentService(), new \\Slotera\\Application\\Services\\CouponService());
    $package=['price'=>75,'extra_services_json'=>json_encode([['id'=>1,'name'=>'Delivery','price'=>25,'price_type'=>'once','active'=>1]])];
    echo json_encode($svc->quote($package,[1],'SAVE10','buyer@example.test'));
  }`;
  const fixtureDir = mkdtempSync(path.join(tmpdir(), 'sltr-rc631-'));
  const tmp = path.join(fixtureDir, 'simple-extras.php');
  let run;
  try {
    fs.writeFileSync(tmp, php);
    run = spawnSync('php', [tmp], { encoding: 'utf8' });
  } finally {
    rmSync(fixtureDir, { recursive: true, force: true });
  }
  assert.equal(run.status, 0, run.stderr);
  const q = JSON.parse(run.stdout);
  assert.equal(q.extras_amount, 25);
  assert.equal(q.selected_extras[0].name, 'Delivery');
  assert.equal(q.dynamic_adjustment_amount, -5);
  assert.equal(q.coupon_discount_amount, 9.5);
  assert.equal(q.final_amount, 85.5);
});

test('RC63.1 frontend offers Simple extras before submit and requotes server-side', () => {
  const js = fs.readFileSync(path.join(root, 'assets/js/frontend-booking-form.js'), 'utf8');
  const view = fs.readFileSync(path.join(root, 'includes/Frontend/Views/booking-form.php'), 'utf8');
  assert.match(view, /data-extra-services=/);
  assert.match(view, /sltr-details-extra-services/);
  assert.match(js, /function renderSimpleExtras\(/);
  assert.match(js, /function requestSimpleQuote\(/);
  assert.match(js, /action: 'sltr_quote_simple_booking'/);
  assert.match(js, /extra_ids: selectedExtras\.join\(','\)/);
});

test('RC63.1 stores and renders selected extras after booking', () => {
  const handler = fs.readFileSync(path.join(root, 'includes/Application/Services/BookingModes/SimpleBookingModeHandler.php'), 'utf8');
  assert.match(handler, /'extras_amount' => \(float\) \(\$quote\['extras_amount'\]/);
  assert.match(handler, /'selected_extras' => \(array\) \(\$quote\['selected_extras'\]/);
  for (const file of ['includes/Frontend/Views/thank-you.php','includes/Frontend/Views/account.php','includes/Admin/Views/booking-single.php']) {
    const source = fs.readFileSync(path.join(root, file), 'utf8');
    assert.match(source, /selected_extras_json/);
    assert.match(source, /line_amount/);
  }
});
