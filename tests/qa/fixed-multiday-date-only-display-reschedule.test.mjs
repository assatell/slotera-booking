import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const helper = read('includes/helpers.php');
const notification = read('includes/Application/Services/EmailNotificationService.php');
const reminder = read('includes/Application/Services/EmailReminderService.php');
const controller = read('includes/Frontend/Controllers/BookingController.php');
const lifecycle = read('includes/Application/Services/BookingLifecycleService.php');
const view = read('includes/Frontend/Views/public-token/reschedule-form.php');
const translations = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
const order = read('includes/Application/Services/Translations/TranslationStringOrder.php');

assert.match(helper, /date_only_multi_day/);
assert.match(helper, /!\$date_only_multi_day && \$event_use_time/);
assert.match(helper, /'time' => \$no_datetime \? '' : \$time/);
assert.match(helper, /'date_only' => \$date_only_multi_day/);
assert.match(helper, /'use_time' => !\$date_only_multi_day/);

assert.match(notification, /'date_only' => !empty\(\$display\['date_only'\]\)/);
assert.match(notification, /if \(!empty\(\$preferences\['date_only'\]\)\)/);
assert.match(reminder, /'hide_time' => \$hide_time \|\| !empty\(\$display\['date_only'\]\)/);

assert.match(controller, /is_fixed_full_day_booking/);
assert.match(controller, /available_full_day_reschedule_dates/);
assert.match(controller, /timed_range_is_available/);
assert.match(controller, /get_available_slots_for_package_date/);
assert.match(controller, /'time' => \$full_day_booking \? ''/);

assert.match(lifecycle, /\$duration_days/);
assert.match(lifecycle, /\$new_end_date/);
assert.match(lifecycle, /'start_time' => '00:00:00'/);
assert.match(lifecycle, /'end_time' => '00:00:00'/);
assert.match(lifecycle, /'end_date' => \$new_end_date/);

assert.match(view, /full_day_booking/);
assert.match(view, /available_dates/);
assert.match(view, /name="sltr_date"/);
assert.doesNotMatch(view.split('<?php if ($full_day_booking) : ?>')[1].split('<?php else : ?>')[0], /sltr_slot|Available times|Find available times/);

assert.match(translations, /'frontend\.select_end_date'/);
for (const locale of ['de_DE','nl_NL','pl_PL','lt_LT','lv','bg_BG','et','fr_FR','it_IT','es_ES','ru_RU','pt_PT','pt_BR','ro_RO','sv_SE','da_DK','fi','cs_CZ','cs','sk_SK','sk','hu_HU','hu','el_GR','el','hr','hr_HR','sl_SI','sl','no_NO','no','nb_NO','nb','is_IS','is','ga','ga_IE','mt_MT']) {
  assert.match(translations, new RegExp(`'${locale}' =>`));
}
assert.match(order, /'frontend\.select_end_date' => 'frontend'/);

console.log('Fixed multi-day bookings are date-only in display/email/account and reschedule by available start date only.');


const pdf = read('includes/Application/Services/PdfInvoiceService.php');
assert.match(pdf, /if \(\$mode === 'simple'\)[\s\S]*return \['date' => '', 'time' => ''\]/);
assert.match(pdf, /\$date_only = \$mode === 'fixed' && !empty\(\$active\['full_day_booking'\]\)/);
assert.match(pdf, /\$start_time = \$date_only \? '' : substr/);
assert.match(pdf, /\.\.\.\(\$date !== '' \? \[\[\$this->invoice_label\('Date'\), \$date\]\] : \[\]\)/);
assert.match(pdf, /\.\.\.\(\$time !== '' \? \[\[\$this->invoice_label\('Time'\), \$time\]\] : \[\]\)/);
assert.match(pdf, /\.\.\.\(\$date !== '' \? \[\$this->invoice_label\('Date'\) => \$date\] : \[\]\)/);
console.log('Invoice sentinel date/time suppression guards are present.');
