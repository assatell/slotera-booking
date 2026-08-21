import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const registry = read('includes/Application/Services/EmailTemplateRegistry.php');
const reminders = read('includes/Application/Services/EmailReminderService.php');
const actions = read('includes/Admin/Controllers/BookingActionsController.php');
const transitions = read('includes/Application/Services/BookingStatusTransitionService.php');
const payments = read('includes/Application/Services/PaymentService.php');

assert.match(actions, /cancel_booking\(\$id\)/);
assert.match(actions, /complete_booking\(\$id\)/);
assert.match(actions, /mark_paid\(\$id\)/);
assert.match(transitions, /notifications->booking_cancelled/);
assert.match(transitions, /notifications->booking_completed/);
assert.match(payments, /notifications->payment_completed/);

assert.match(registry, /public static function scenarios_for_locale/);
assert.match(registry, /public static function resolve_runtime_payload_for_locale/);
assert.match(reminders, /\$email_locale = \$this->booking_email_locale\(\$booking\)/);
assert.match(reminders, /EmailTemplateRegistry::scenarios_for_locale\(\$email_locale\)/);
assert.match(reminders, /EmailTemplateRegistry::resolve_runtime_payload_for_locale/);
assert.match(reminders, /'email_locale' => \$email_locale/);
assert.match(reminders, /booking_locale/);
assert.match(reminders, /sltr_booking_status_label\(\$status, 'emails', \$locale\)/);
assert.match(reminders, /sltr_payment_status_label\(\$status, 'emails', \$locale\)/);

console.log('Admin booking/payment actions queue customer emails in the booking locale, not the admin runtime locale.');
