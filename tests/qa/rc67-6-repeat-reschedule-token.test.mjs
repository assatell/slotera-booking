import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const lifecycle = fs.readFileSync(path.join(root, 'includes/Application/Services/BookingLifecycleService.php'), 'utf8');
const repository = fs.readFileSync(path.join(root, 'includes/Infrastructure/Repositories/BookingRepository.php'), 'utf8');

test('customer reschedule rotates its token and permits later reschedules', () => {
  assert.doesNotMatch(
    lifecycle,
    /count_event\([^\n]+booking_rescheduled_by_customer/,
    'booking history must not impose a lifetime one-reschedule limit',
  );
  assert.equal(
    (lifecycle.match(/generate_unique_token\('reschedule_token', \$repo\)/g) || []).length,
    2,
    'timed and multi-day reschedules must both generate replacement tokens',
  );
  assert.equal(
    (lifecycle.match(/reschedule_by_token_atomically/g) || []).length,
    2,
    'timed and multi-day reschedules must both consume their current token atomically',
  );
});

test('repository accepts only the request that still owns the current token', () => {
  assert.match(
    repository,
    /function reschedule_by_token_atomically\(int \$id, string \$token, array \$data\): bool/,
  );
  assert.match(
    repository,
    /\['id' => \$id, 'reschedule_token' => \$token\]/,
    'atomic update must compare the stored token with the presented token',
  );
  assert.match(
    repository,
    /hash_equals\(\$token, \$next_token\)/,
    'replacement token must differ from the consumed token',
  );
  assert.match(
    repository,
    /\$data\['active_slot_hash'\] = \$this->active_slot_hash_from_data\(\$merged\)/,
    'atomic reschedule must preserve active-slot uniqueness',
  );
});
