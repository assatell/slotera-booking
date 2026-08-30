<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}


final class EmailTemplateRegistry
{
    public static function scenarios(): array
    {
        // Email templates are editable content. They use the Email Templates
        // section default language as the default editing/sending context.
        return self::scenarios_for_locale(self::runtime_locale());
    }

    public static function scenarios_for_locale(string $locale): array
    {
        return self::localized_scenarios(self::base_scenarios(), $locale);
    }

    public static function resolve_runtime_value(string $scenario_key, string $field, ?string $stored_value): string
    {
        return self::resolve_runtime_value_for_locale($scenario_key, $field, $stored_value, self::runtime_locale());
    }

    public static function resolve_runtime_value_for_locale(string $scenario_key, string $field, ?string $stored_value, string $locale): string
    {
        $scenarios = self::scenarios_for_locale($locale);
        $localized_default = isset($scenarios[$scenario_key][$field]) ? (string) $scenarios[$scenario_key][$field] : '';

        if ($stored_value === null || self::is_effectively_empty_template($stored_value)) {
            return $localized_default;
        }

        // Old Latvian stock templates were stored in several normalized forms
        // (plain text, wp_kses HTML and queue payload variants). Exact
        // fingerprint comparison can miss those forms. Replace only the
        // unmistakable stock markers; genuinely customized copy is preserved.
        if (self::is_legacy_latvian_stock_value($scenario_key, $field, $stored_value)) {
            return $localized_default;
        }

        $stored_fingerprint = self::template_fingerprint($stored_value);
        foreach (self::known_default_values($scenario_key, $field) as $known_default) {
            if ($stored_fingerprint === self::template_fingerprint((string) $known_default)) {
                return $localized_default;
            }
        }

        return $stored_value;
    }


    /** Resolve the exact subject/body pair stored in an email queue item. */
    public static function resolve_runtime_payload(string $scenario_key, ?string $stored_subject, ?string $stored_body, ?string $stored_html_body, bool $use_html): array
    {
        return self::resolve_runtime_payload_for_locale($scenario_key, $stored_subject, $stored_body, $stored_html_body, $use_html, self::runtime_locale());
    }

    public static function resolve_runtime_payload_for_locale(string $scenario_key, ?string $stored_subject, ?string $stored_body, ?string $stored_html_body, bool $use_html, string $locale): array
    {
        $subject = self::resolve_runtime_value_for_locale($scenario_key, 'default_subject', $stored_subject, $locale);
        $plain_body = self::resolve_runtime_value_for_locale($scenario_key, 'default_body', $stored_body, $locale);
        $html_body = self::resolve_runtime_value_for_locale($scenario_key, 'default_html_body', $stored_html_body, $locale);
        $is_html = $use_html && !self::is_effectively_empty_template($html_body);
        if (!$is_html) {
            $plain_body = self::normalize_plain_text_template($plain_body);
        }

        return [
            'subject' => $subject,
            'body' => $is_html ? $html_body : $plain_body,
            'is_html_template' => $is_html,
        ];
    }


    private static function is_legacy_latvian_stock_value(string $scenario_key, string $field, string $stored_value): bool
    {
        if (self::runtime_locale() !== 'lv_LV') {
            return false;
        }

        $normalized = self::template_fingerprint($stored_value);

        if ($scenario_key === 'booking_completed_customer' && $field === 'default_subject') {
            return [
                    'Ευχαριστούμε για την επίσκεψή σας',
                    'Paldies par apmeklējumu',
                    'Takk for besøket',
                    'Köszönjük látogatását',
                    'Hvala za vaš obisk',
                    'Ďakujeme za návštevu',
                    'Vă mulțumim pentru vizită',
                    'Obrigado pela sua visita',
                    'Děkujeme za návštěvu',
                    'Dziękujemy za wizytę',
                    'Agradecemos pela sua visita',
                    'Grazie per la tua visita',
                    'Gracias por tu visita',
                    'Kiitos käynnistäsi',
                    'Tack för ditt besök',
                    'Merci pour votre visite',
                    'Благодарим ви за посещението',
                    'Jūsų užsakymas įvykdytas',
                    'Täname külastuse eest',
                    'Спасибо за ваш визит',
                    'Tak for dit besøg',
                    'Bedankt voor uw bezoek',
                    'Vielen Dank für Ihren Besuch',
                    'Hvala na posjetu',
                    'Thank you for your visit',
            ];
        }

        if ($scenario_key === 'booking_completed_customer' && in_array($field, ['default_body', 'default_html_body'], true)) {
            return [
                    'Γεια σας {customer_name},

Ευχαριστούμε για την επίσκεψή σας. Η κράτησή σας έχει πλέον ολοκληρωθεί.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}',
                    'Labdien, {customer_name}!
            
            Paldies par apmeklējumu. Jūsu rezervācija ir pabeigta.
            
            Pakalpojums: {package_title}
            Datums: {booking_date}
            Laiks: {start_time} - {end_time}
            
            Rezervācijas numurs: #{booking_id}',
                    'Hei, {customer_name}!

Takk for besøket. Bestillingen din er nå fullført.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}',
                    'Kedves {customer_name}!

Köszönjük látogatását. Foglalása teljesült.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}',
                    'Pozdravljeni, {customer_name},

Hvala za vaš obisk. Vaša rezervacija je zdaj zaključena.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}',
                    'Dobrý deň, {customer_name},

Ďakujeme za návštevu. Vaša rezervácia je teraz dokončená.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}',
                    'Bună ziua, {customer_name},

Vă mulțumim pentru vizită. Rezervarea dvs. este acum finalizată.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}',
                    'Olá {customer_name},

Obrigado pela sua visita. A sua reserva está agora concluída.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}',
                    'Dobrý den, {customer_name},

Děkujeme za návštěvu. Vaše rezervace je nyní dokončena.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}',
                    'Dzień dobry {customer_name},

Dziękujemy za wizytę. Twoja rezerwacja została zakończona.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}',
                    'Olá {customer_name},

Agradecemos pela sua visita. Sua reserva foi concluída.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}',
                    'Ciao {customer_name},

grazie per la tua visita. La prenotazione è ora completata.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}',
                    'Hola {customer_name},

Gracias por tu visita. Tu reserva ya está completada.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}',
                    'Hei {customer_name},

Kiitos käynnistäsi. Varauksesi on nyt valmis.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}',
                    'Hej {customer_name},

Tack för ditt besök. Din bokning är nu slutförd.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}',
                    'Bonjour {customer_name},

Merci pour votre visite. Votre réservation est maintenant terminée.

Service : {package_title}
Date : {booking_date}
Heure : {start_time} - {end_time}

Numéro de réservation : #{booking_id}',
                    'Здравейте, {customer_name},\\n\\nБлагодарим ви за посещението. Вашата резервация вече е завършена.\\n\\nУслуга: {package_title}\\nДата: {booking_date}\\nЧас: {start_time} - {end_time}\\n\\nНомер на резервация: #{booking_id}',
                    'Sveiki, {customer_name},

Dėkojame, kad apsilankėte. Jūsų užsakymas įvykdytas.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}',
                    'Tere, {customer_name},

Täname külastuse eest. Teie broneering on nüüd lõpetatud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}',
                    'Здравствуйте, {customer_name},

Спасибо за ваш визит. Ваше бронирование завершено.

Услуга: {package_title}
Дата: {booking_date}
Время: {start_time} - {end_time}

Номер бронирования: #{booking_id}',
                    'Hej {customer_name},

Tak for dit besøg. Din booking er nu gennemført.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}',
                    'Hallo {customer_name},

Bedankt voor uw bezoek. Uw boeking is nu voltooid.

Dienst: {package_title}
Datum: {booking_date}
Tijd: {start_time} - {end_time}

Boekingsnummer: #{booking_id}',
                    'Hallo {customer_name},

vielen Dank für Ihren Besuch. Ihre Buchung ist nun abgeschlossen.

Leistung: {package_title}
Datum: {booking_date}
Uhrzeit: {start_time} - {end_time}

Buchungsnummer: #{booking_id}',
                    'Pozdrav {customer_name},

Hvala na posjetu. Vaša je rezervacija sada dovršena.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}',
                    'Hello {customer_name},

Thank you for your visit. Your booking is now completed.

Service: {package_title}
Date: {booking_date}
Time: {start_time} - {end_time}

Booking number: #{booking_id}',
            ];
        }

        if ($scenario_key === 'booking_created_customer' && $field === 'default_subject') {
            return $normalized === 'Saņemts jūsu rezervācijas pieprasījums';
        }

        if ($scenario_key === 'booking_created_customer' && in_array($field, ['default_body', 'default_html_body'], true)) {
            $required = [
                'Paldies par rezervāciju.',
                'Esam saņēmuši jūsu pieprasījumu.',
                'Mainīt rezervācijas laiku:',
                '{customer_name}',
                '{booking_id}',
            ];
            foreach ($required as $marker) {
                if (strpos($normalized, $marker) === false) {
                    return false;
                }
            }
            return true;
        }

        if ($scenario_key === 'booking_created_admin' && in_array($field, ['default_body', 'default_html_body'], true)) {
            $required = [
                'Saņemta jauna rezervācija.',
                'Klients:',
                'Email:',
                '{customer_email}',
                '{booking_id}',
            ];
            foreach ($required as $marker) {
                if (strpos($normalized, $marker) === false) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    private static function normalize_plain_text_template(string $value): string
    {
        // Localized registry entries are PHP single-quoted strings and may
        // therefore contain literal \n/\r/\t sequences. Convert them before
        // line-based display filters run; otherwise a filter can treat the
        // entire template as one line and remove the whole message body.
        return str_replace(['\\r\\n', '\\n', '\\r', '\\t'], ["\n", "\n", "\n", "\t"], $value);
    }

    private static function is_effectively_empty_template(string $value): bool
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(['\\n', '\\r', '\\t'], ' ', $value);
        $value = preg_replace('/<(?:br|hr)\s*\/?\s*>/iu', ' ', $value) ?? $value;
        $value = wp_strip_all_tags($value, true);
        $value = preg_replace('/[\s\x{00A0}]+/u', '', $value) ?? $value;
        return $value === '';
    }

    /**
     * Return true when a saved value is an untouched or mixed-language stock
     * template that is safe to replace during the 1.0.699 data migration.
     * Genuine custom copy is preserved.
     */
    public static function is_repairable_stock_value(string $scenario_key, string $field, ?string $stored_value): bool
    {
        if ($stored_value === null || trim($stored_value) === '') {
            return true;
        }

        $stored_fingerprint = self::template_fingerprint($stored_value);
        foreach (self::known_default_values($scenario_key, $field) as $known_default) {
            if ($stored_fingerprint === self::template_fingerprint((string) $known_default)) {
                return true;
            }
        }

        if (!in_array($field, ['default_body', 'default_html_body'], true)) {
            return false;
        }

        // 1.0.695-1.0.698 could save a localized heading around the old Russian
        // stock body. Detect that hybrid by its stock placeholders and several
        // unmistakable Russian labels, rather than requiring an exact match.
        $required_placeholders = ['{customer_name}', '{package_title}', '{booking_date}', '{start_time}', '{booking_id}'];
        foreach ($required_placeholders as $placeholder) {
            if (strpos($stored_value, $placeholder) === false) {
                return false;
            }
        }

        $russian_markers = [
            'Здравствуйте', 'Спасибо за бронирование', 'Услуга:', 'Дата:',
            'Время:', 'Статус:', 'Номер бронирования:', 'Отменить бронирование:',
            'Перенести бронирование:', 'Оплата:', 'Сводка стоимости:',
        ];
        $marker_count = 0;
        foreach ($russian_markers as $marker) {
            if (mb_stripos($stored_value, $marker, 0, 'UTF-8') !== false) {
                $marker_count++;
            }
        }

        return $marker_count >= 4;
    }

    private static function template_fingerprint(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/<br\s*\/?\s*>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\/(?:p|div|li|tr|h[1-6])>/i', "\n", $value) ?? $value;
        $value = wp_strip_all_tags($value, true);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/ *\n */u', "\n", $value) ?? $value;
        $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?? $value;
        return trim($value);
    }

    public static function known_default_values(string $scenario_key, string $field): array
    {
        $values = [];
        $base = self::base_scenarios();
        if (isset($base[$scenario_key][$field])) {
            $values[] = (string) $base[$scenario_key][$field];
        }

        foreach (self::legacy_default_values($scenario_key, $field) as $legacy_default) {
            $values[] = $legacy_default;
        }

        foreach (array_keys(self::scenario_translations()) as $locale) {
            $localized = self::localized_scenarios($base, $locale);
            if (isset($localized[$scenario_key][$field])) {
                $values[] = (string) $localized[$scenario_key][$field];
            }
        }

        foreach (array_values($values) as $value) {
            foreach (self::legacy_polish_variants((string) $value) as $legacyValue) {
                $values[] = $legacyValue;
            }
        }

        return array_values(array_unique($values));
    }

    private static function legacy_polish_variants(string $value): array
    {
        $variants = [];
        $replacements = [
            'Ripianifica prenotazione' => 'Riprogramma prenotazione',
            'Elige una nueva fecha' => 'Elija una nueva fecha',
            'Buchungsstatus:' => 'Status:',
            'Bokningsstatus:' => 'Status:',
            'Afbestil booking' => 'Annuller booking',
            'Bedankt voor je boeking.' => 'Bedankt voor uw boeking.',
            'We hebben je aanvraag ontvangen.' => 'We hebben uw aanvraag ontvangen.',
        ];
        foreach ($replacements as $current => $legacy) {
            if (strpos($value, $current) !== false) {
                $variants[] = str_replace($current, $legacy, $value);
            }
        }
        return $variants;
    }

    private static function legacy_default_values(string $scenario_key, string $field): array
    {
        // Historical defaults may already be stored in wp_options. They are
        // editable settings, but untouched stock copies must follow the active
        // Email Templates locale after an upgrade or language change.
        if ($scenario_key === 'booking_created_customer' && $field === 'default_subject') {
            return [
                'Saņemts jūsu rezervācijas pieprasījums',
            ];
        }

        if ($scenario_key === 'booking_created_customer' && in_array($field, ['default_body', 'default_html_body'], true)) {
            return [
                "Здравствуйте, {customer_name},

Спасибо за бронирование. Мы получили ваш запрос.

Услуга: {package_title}
Дата: {booking_date}
Время: {start_time} - {end_time}
Статус: {status_label}

Номер бронирования: #{booking_id}

Отменить бронирование: {cancellation_url}
Перенести бронирование: {reschedule_url}",
                "Labdien, {customer_name}!

Paldies par rezervāciju. Esam saņēmuši jūsu pieprasījumu.

Pakalpojums: {package_title}
Datums: {booking_date}
Laiks: {start_time} - {end_time}
Statuss: {status_label}

Rezervācijas numurs: #{booking_id}

Atcelt rezervāciju: {cancellation_url}
Mainīt rezervācijas laiku: {reschedule_url}",
            ];
        }

        if ($scenario_key === 'booking_created_admin' && $field === 'default_body') {
            return [
                "Saņemta jauna rezervācija.

Klients: {customer_name}
Email: {customer_email}
Tālrunis: {customer_phone}
Pakalpojums: {package_title}
Datums: {booking_date}
Laiks: {start_time} - {end_time}
Statuss: {status_label}

Rezervācijas numurs: #{booking_id}",
            ];
        }

        return [];
    }

    private static function base_scenarios(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [
            'booking_created_customer' => [
                'title' => 'Booking created — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when a booking is created.',
                'default_subject' => 'Your booking request has been received',
                'default_body' => "Hello {customer_name},\n\nThank you for your booking. We have received your request.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nStatus: {status_label}\nPayment: {payment_status_label}\n\nPrice summary:\n{price_summary}\n\nBooking number: #{booking_id}\n\nCancel booking: {cancellation_url}\nReschedule booking: {reschedule_url}",
            ],
            'booking_created_admin' => [
                'title' => 'New booking — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when a new booking is created.',
                'default_subject' => 'New booking received',
                'default_body' => "New booking received.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nStatus: {status_label}\nPayment: {payment_status_label}\n\nPrice summary:\n{price_summary}\n\nBooking number: #{booking_id}",
            ],
            'booking_confirmed_customer' => [
                'title' => 'Booking confirmed — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when a booking is confirmed.',
                'default_subject' => 'Your booking is confirmed',
                'default_body' => "Hello {customer_name},\n\nYour booking is confirmed.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}\n\nCancel booking: {cancellation_url}\nReschedule booking: {reschedule_url}",
            ],
            'booking_confirmed_admin' => [
                'title' => 'Booking confirmed — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when a booking is confirmed.',
                'default_subject' => 'Booking confirmed: #{booking_id}',
                'default_body' => "A booking has been confirmed.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nBooking number: #{booking_id}",
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Reminder 24h — customer',
                'recipient' => 'customer',
                'description' => 'Queued automatically 24 hours before a confirmed booking.',
                'default_subject' => 'Reminder: your booking is tomorrow',
                'default_body' => "Hello {customer_name},\n\nThis is a reminder for your upcoming booking.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nCancel booking: {cancellation_url}\nReschedule booking: {reschedule_url}",
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Reminder 2h — customer',
                'recipient' => 'customer',
                'description' => 'Queued automatically 2 hours before a confirmed booking.',
                'default_subject' => 'Reminder: your booking starts soon',
                'default_body' => "Hello {customer_name},\n\nYour booking starts soon.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}",
            ],
            'booking_cancelled_customer' => [
                'title' => 'Booking cancelled — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when a booking is cancelled.',
                'default_subject' => 'Your booking has been cancelled',
                'default_body' => "Hello {customer_name},\n\nYour booking has been cancelled.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}",
            ],
            'booking_cancelled_admin' => [
                'title' => 'Booking cancelled — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when a booking is cancelled.',
                'default_subject' => 'Booking cancelled: #{booking_id}',
                'default_body' => "A booking has been cancelled.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}",
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Booking rescheduled — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when a booking is rescheduled.',
                'default_subject' => 'Your booking has been rescheduled',
                'default_body' => "Hello {customer_name},\n\nYour booking has been rescheduled.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}\n\nCancel booking: {cancellation_url}\nReschedule booking: {reschedule_url}",
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Booking rescheduled — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when a booking is rescheduled.',
                'default_subject' => 'Booking rescheduled: #{booking_id}',
                'default_body' => "A booking has been rescheduled.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nStatus: {status_label}\nPayment: {payment_status_label}\nBooking number: #{booking_id}",
            ],
            'booking_completed_customer' => [
                'title' => 'Booking completed — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when a booking is marked completed.',
                'default_subject' => 'Thank you for choosing us.',
                'default_body' => "Hello {customer_name},\n\nThank you for choosing us. Your booking is now completed.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}",
            ],
            'booking_completed_admin' => [
                'title' => 'Booking completed — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when a booking is marked completed.',
                'default_subject' => 'Booking completed: #{booking_id}',
                'default_body' => "A booking has been completed.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nBooking number: #{booking_id}",
            ],
            'package_changed_customer' => [
                'title' => 'Package changed — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when the booking service/package is changed.',
                'default_subject' => 'Your booking service has been changed',
                'default_body' => "Hello {customer_name},\n\nThe service for your booking has been changed.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}",
            ],
            'package_changed_admin' => [
                'title' => 'Package changed — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when the booking service/package is changed.',
                'default_subject' => 'Booking service changed: #{booking_id}',
                'default_body' => "The service for a booking has been changed.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nBooking number: #{booking_id}",
            ],
            'payment_pending_customer' => [
                'title' => 'Payment pending — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when payment is pending or awaiting action.',
                'default_subject' => 'Payment is pending for your booking',
                'default_body' => "Hello {customer_name},\n\nYour booking payment is pending.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nPayment: {payment_status_label}\n\nPrice summary:\n{price_summary}\n\nBooking number: #{booking_id}",
            ],
            'payment_pending_admin' => [
                'title' => 'Payment pending — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when payment is pending or awaiting action.',
                'default_subject' => 'Payment pending for booking #{booking_id}',
                'default_body' => "Payment is pending.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nPayment: {payment_status_label}\n\nPrice summary:\n{price_summary}\n\nBooking number: #{booking_id}",
            ],
            'payment_received_customer' => [
                'title' => 'Payment confirmation — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when payment is confirmed.',
                'default_subject' => 'Payment received',
                'default_body' => "Hello {customer_name},\n\nWe have received your payment.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nPayment: {payment_status_label}\n\nPrice summary:\n{price_summary}\n\nBooking number: #{booking_id}",
            ],
            'payment_received_admin' => [
                'title' => 'Payment confirmation — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when payment is confirmed.',
                'default_subject' => 'Payment received for booking #{booking_id}',
                'default_body' => "Payment received.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nPayment: {payment_status_label}\n\nPrice summary:\n{price_summary}\n\nBooking number: #{booking_id}",
            ],
            'payment_failed_customer' => [
                'title' => 'Payment failed — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when payment fails.',
                'default_subject' => 'Payment failed',
                'default_body' => "Hello {customer_name},\n\nYour payment could not be completed.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}",
            ],
            'payment_failed_admin' => [
                'title' => 'Payment failed — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when payment fails.',
                'default_subject' => 'Payment failed for booking #{booking_id}',
                'default_body' => "Payment failed.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nPayment: {payment_status_label}\nBooking number: #{booking_id}",
            ],
            'payment_refunded_customer' => [
                'title' => 'Payment refunded — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when payment is refunded.',
                'default_subject' => 'Your payment has been refunded',
                'default_body' => "Hello {customer_name},\n\nYour payment has been refunded.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nBooking number: #{booking_id}",
            ],
            'payment_refunded_admin' => [
                'title' => 'Payment refunded — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when payment is refunded.',
                'default_subject' => 'Payment refunded for booking #{booking_id}',
                'default_body' => "Payment refunded.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nBooking number: #{booking_id}",
            ],
            'invoice_created_customer' => [
                'title' => 'Invoice created — customer',
                'recipient' => 'customer',
                'description' => 'Queued for the customer when an invoice is created.',
                'default_subject' => 'Invoice for booking #{booking_id}',
                'default_body' => "Hello {customer_name},\n\nAn invoice has been created for your booking.\n\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nPrice summary:\n{price_summary}\n\nBooking number: #{booking_id}",
            ],
            'invoice_created_admin' => [
                'title' => 'Invoice created — admin',
                'recipient' => 'admin',
                'description' => 'Queued for the admin when an invoice is created.',
                'default_subject' => 'Invoice created for booking #{booking_id}',
                'default_body' => "An invoice has been created.\n\nCustomer: {customer_name}\nEmail: {customer_email}\nService: {package_title}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nBooking number: #{booking_id}",
            ],
            'magic_link_customer' => [
                'title' => 'Magic link — customer',
                'recipient' => 'customer',
                'description' => 'Template for future client login emails.',
                'default_subject' => 'Your login link',
                'default_body' => "Hello {customer_name},\n\nUse this link to log in to your account:\n\n{magic_link}\n\nThis link expires soon.",
            ],

            'contact_form_admin' => [
                'title' => 'Contact form — admin',
                'recipient' => 'admin',
                'description' => 'Sent to the admin when a visitor submits the Slotera contact form.',
                'default_subject' => '[{site_name}] New contact message',
                'default_body' => "New contact form message.\n\nName: {contact_name}\nEmail: {contact_email}\nPhone: {contact_phone}\nSubject: {contact_subject}\nMessage:\n{contact_message}\n\nPage: {contact_page_title}\nURL: {contact_page_url}\nSubmitted: {contact_submitted_at}\nLocale: {contact_locale}",
            ],
            'marketing_promo' => [
                'title' => 'Marketing — promo',
                'type' => 'marketing',
                'recipient' => 'customer',
                'description' => 'Reusable marketing template for promotional campaigns, offers and comeback emails.',
                'default_subject' => '{headline}',
                'default_body' => "Hello {customer_name},\n\n{headline}\n\n{message}\n\n{submessage}\n\n{coupon_code}\n\n{cta_url}",
                'default_use_html' => 1,
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Special offer</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
    <p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Your offer code</p>
    <p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
    <p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · valid until {coupon_expires}</p>
  </div>
</div>',
            ],
        ];

        return $cache;
    }

    /** Translation QA accessors used by Diagnostics only. */
    public static function qa_base_scenarios(): array
    {
        return self::base_scenarios();
    }

    public static function qa_locales(): array
    {
        $locales = [];
        foreach (array_keys(self::scenario_translations()) as $locale) {
            $canonical = self::canonical_locale((string) $locale);
            if (TranslationRegistry::is_visible_locale($canonical)) {
                $locales[$canonical] = true;
            }
        }
        return array_keys($locales);
    }

    public static function qa_scenarios_for_locale(string $locale): array
    {
        return self::localized_scenarios(self::base_scenarios(), $locale);
    }

    /** Raw translated fields for QA. Missing locale/fields must stay missing. */
    public static function qa_translation_fields_for_locale(string $locale): array
    {
        $locale = self::storage_locale($locale);
        return self::scenario_translations()[$locale] ?? [];
    }

    private static function localized_scenarios(array $scenarios, string $locale): array
    {
        $translations = self::scenario_translations()[self::storage_locale($locale)] ?? [];
        foreach ($translations as $key => $fields) {
            if (!isset($scenarios[$key])) {
                continue;
            }
            foreach ($fields as $field => $value) {
                $scenarios[$key][$field] = $value;
            }
        }
        return $scenarios;
    }

    private static function canonical_locale(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        $aliases = [
            'fi' => 'fi_FI',
            'et' => 'et_EE',
            'el' => 'el_GR',
            'hr' => 'hr_HR',
            'lv' => 'lv_LV',
            'nb_NO' => 'no_NO',
            'nb' => 'no_NO',
        ];
        return $aliases[$locale] ?? $locale;
    }

    private static function storage_locale(string $locale): string
    {
        $canonical = self::canonical_locale($locale);
        $storageAliases = [
            'fi_FI' => 'fi',
        ];
        return $storageAliases[$canonical] ?? $canonical;
    }

    public static function runtime_locale(): string
    {
        $stored = function_exists('get_option') ? get_option('sltr_translation_context_locales', []) : [];
        $locale = is_array($stored) ? (string) ($stored['emails'] ?? 'en_US') : 'en_US';
        $locale = str_replace('-', '_', trim($locale));
        if ($locale === 'bg' || $locale === 'bg_BG') {
            return 'bg_BG';
        }
        if ($locale === 'et' || $locale === 'et_EE') {
            return 'et_EE';
        }
        if ($locale === 'ru' || $locale === 'ru_RU') {
            return 'ru_RU';
        }
        if ($locale === 'de' || $locale === 'de_DE') {
            return 'de_DE';
        }
        if ($locale === 'fr' || $locale === 'fr_FR') {
            return 'fr_FR';
        }
        if ($locale === 'fi' || $locale === 'fi_FI') {
            return 'fi_FI';
        }
        if ($locale === 'nb' || $locale === 'nb_NO' || $locale === 'no' || $locale === 'no_NO') {
            return 'no_NO';
        }
        $locale = self::canonical_locale($locale);
        return TranslationRegistry::is_visible_locale($locale) ? $locale : 'en_US';
    }

    private static function scenario_translations(): array
    {
        return EmailTemplateTranslationData::all();
    }

    public static function placeholders(): array
    {
        return [
            '{booking_id}', '{customer_name}', '{customer_email}', '{customer_phone}', '{package_title}',
            '{booking_date}', '{start_time}', '{end_time}', '{status}', '{payment_status}', '{status_raw}', '{payment_status_raw}', '{status_label}', '{payment_status_label}', '{site_name}', '{magic_link}', '{cancellation_url}', '{reschedule_url}',
            '{base_amount}', '{package_discount}', '{coupon_code}', '{coupon_discount}', '{coupon_expires}', '{discount_amount}', '{final_amount}', '{total_amount}', '{tax_amount}', '{price_summary}',
            '{theme_primary_color}', '{theme_primary_text_color}', '{theme_text_color}', '{theme_muted_text_color}', '{theme_card_background_color}',
            '{contact_name}', '{contact_email}', '{contact_phone}', '{contact_subject}', '{contact_message}', '{contact_page_url}', '{contact_page_title}', '{contact_submitted_at}', '{contact_locale}',
            '{headline}', '{message}', '{submessage}', '{cta_button}', '{booking_url}', '{package_url}', '{cta_url}',
        ];
    }

    /**
     * Return placeholders as a lookup map for strict template validation.
     *
     * E-mail template bodies may contain HTML and styles, but Slotera placeholder
     * tokens are intentionally limited to this explicit registry. Unknown
     * {token_name} patterns are removed on save so accidental typos or injected
     * placeholder-like values cannot survive into outbound e-mail rendering.
     */
    public static function placeholder_map(): array
    {
        return array_fill_keys(self::placeholders(), true);
    }

    public static function sanitize_template_placeholders(string $content): string
    {
        $allowed = self::placeholder_map();

        return (string) preg_replace_callback('/\{[A-Za-z0-9_]+\}/', static function (array $matches) use ($allowed): string {
            $placeholder = (string) ($matches[0] ?? '');
            return isset($allowed[$placeholder]) ? $placeholder : '';
        }, $content);
    }
}
