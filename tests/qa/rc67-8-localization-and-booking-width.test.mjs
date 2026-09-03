import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('contact form follows the canonical frontend locale and translation registry', () => {
  const shortcode = read('includes/Frontend/Shortcodes/BookingShortcode.php');
  const view = read('includes/Frontend/Views/contact-form.php');
  const translations = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
  const order = read('includes/Application/Services/Translations/TranslationStringOrder.php');

  assert.doesNotMatch(shortcode, /HTTP_ACCEPT_LANGUAGE/);
  assert.match(shortcode, /TranslationService\(\)\)->locale_for_group\('frontend'\)/);
  assert.doesNotMatch(shortcode, /\$this->contact_supported_locales\(\)/);
  assert.doesNotMatch(shortcode, /isset\(\$_POST\['sltr_contact_locale'\]\)/);
  assert.doesNotMatch(view, /name="sltr_contact_locale"/);

  for (const [field, value] of Object.entries({
    name: 'Name', email: 'Email', phone: 'Phone', subject: 'Message Subject',
    message: 'Message', company: 'Company website', submit: 'Send Message',
    sent: 'Thank you. Your message has been sent.',
    invalid: 'Please fill in your name, email address and message.',
    spam: 'Message could not be processed. Please try again.',
    failed: 'Message could not be sent. Please try again later.',
  })) {
    assert.ok(shortcode.includes(`'${field}' => '${value}'`), `${field} default missing`);
    const renderedInView = ['name','email','phone','subject','message','company','submit'].includes(field);
    assert.ok(
      renderedInView ? view.includes(`contact_labels['${field}']`) : shortcode.includes(`contact_labels['${field}']`),
      `${field} must render through contact_labels`,
    );
  }
  assert.match(shortcode, /sltr_t\(\$default, 'frontend', \$locale\)/);

  for (const key of [
    'frontend.contact_message_subject', 'frontend.contact_message',
    'frontend.contact_send_message', 'frontend.contact_sent',
    'frontend.contact_invalid', 'frontend.contact_spam', 'frontend.contact_failed',
  ]) {
    const start = translations.indexOf(`'${key}' =>`);
    assert.ok(start >= 0, `${key} missing from frontend catalog`);
    const next = translations.indexOf("\n  'frontend.", start + key.length + 5);
    const block = translations.slice(start, next > start ? next : translations.length);
    for (const locale of ['en_US','et','ru_RU','de_DE','fi','fr_FR','sv_SE','pt_BR','mt_MT']) {
      assert.match(block, new RegExp(`'${locale}'\\s*=>\\s*'`), `${key} missing ${locale}`);
    }
    assert.ok(order.includes(`'${key}' => 'frontend'`), `${key} missing from runtime order`);
  }

  assert.match(translations, /'frontend\.phone'[\s\S]*?'et' => 'Telefon'/);
});

test('French Message is accepted as a correct same-as-English translation', () => {
  const scanner = read('includes/Application/Services/TranslationQualityScanner.php');
  assert.match(scanner, /'fr_FR'\s*=>\s*\[[\s\S]*?'frontend_ui'\s*=>\s*\['frontend\.contact_message'\]/);
});

test('all customer-facing package views use one localized duration formatter', () => {
  const formatter = read('includes/Application/Services/FrontendDurationFormatter.php');
  assert.match(formatter, /sltr_t\('%dh %dmin', 'frontend', \$locale\)/);
  assert.match(formatter, /sltr_t\('%dh', 'frontend', \$locale\)/);
  assert.match(formatter, /sltr_t\('%dmin', 'frontend', \$locale\)/);

  for (const path of [
    'includes/Frontend/Views/categories-list.php',
    'includes/Frontend/Views/packages-list.php',
    'includes/Frontend/Views/booking-form.php',
    'includes/Frontend/Views/package-detail.php',
  ]) {
    const source = read(path);
    assert.match(source, /FrontendDurationFormatter::format/);
    assert.doesNotMatch(source, /sprintf\(sltr_t\('%dh/);
  }

  const translations = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
  assert.match(translations, /'default' => '%dh %dmin'[\s\S]*?'et' => '%d h %d min'/);
  assert.match(translations, /'default' => '%dh %dmin'[\s\S]*?'ru_RU' => '%d ч %d мин'/);
  assert.match(translations, /'default' => '%dh %dmin'[\s\S]*?'de_DE' => '%d Std\. %d Min\.'/);
});

test('booking inner flow grows by 40px without changing outer, mobile, or catalogs', () => {
  const view = read('includes/Frontend/Views/booking-form.php');
  const css = read('assets/css/frontend.css');

  assert.doesNotMatch(view, /booking_flow_max_width|--sltr-booking-flow-max-width|\+ 40\) \. 'px'/);
  assert.match(view, /class="sltr-booking sltr-booking-flow /);
  assert.match(css, /\.sltr-booking-flow \{[^}]*--sltr-booking-calendar-max-width:\s*470px;[^}]*--sltr-booking-inner-max-width:\s*560px;[^}]*--sltr-booking-slots-grid-max-width:\s*660px;/);
  assert.doesNotMatch(css, /\.sltr-booking-flow\s*\{[^}]*(?:^|;)\s*max-width\s*:/m);
  assert.match(css, /\.sltr-booking\s*\{[^}]*max-width:\s*var\(--sltr-booking-form-max-width,\s*1280px\)/);
  assert.match(css, /@media \(max-width: 640px\) \{[\s\S]*?\.sltr-booking \{[\s\S]*?max-width: 100%/);
  assert.doesNotMatch(read('includes/Frontend/Views/packages-list.php'), /sltr-booking-flow-max-width/);
  assert.doesNotMatch(read('includes/Frontend/Views/categories-list.php'), /sltr-booking-flow-max-width/);
  assert.doesNotMatch(read('includes/Frontend/Views/package-detail.php'), /sltr-booking-flow-max-width/);
});
