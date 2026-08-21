<?php

declare(strict_types=1);

namespace Slotera\Frontend\Shortcodes;

use Slotera\Application\Services\BookingAccessTokenService;
use Slotera\Application\Services\AccountMagicLinkService;
use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\BookingSpamProtectionService;
use WP_Error;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\BookingHistoryRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Core\HtmlSanitizer;

if (!defined('ABSPATH')) {
    exit;
}

final class BookingShortcode
{
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_shortcode('slotera_booking', [$this, 'render_package']);
        add_shortcode('slotera_category', [$this, 'render_category']);
        add_shortcode('slotera_packages', [$this, 'render_packages']);
        add_shortcode('slotera_categories', [$this, 'render_categories']);
        add_shortcode('slotera_thank_you', [$this, 'render_thank_you']);
        add_shortcode('slotera_checkout', [$this, 'render_checkout']);
        add_shortcode('slotera_contact', [$this, 'render_contact']);
        add_action('admin_post_sltr_contact_form_submit', [$this, 'handle_contact_form_submit']);
        add_action('admin_post_nopriv_sltr_contact_form_submit', [$this, 'handle_contact_form_submit']);
        add_shortcode('slotera_login', [$this, 'render_login']);
        add_shortcode('slotera_account', [$this, 'render_account']);
        add_shortcode('slotera_package_slider', [$this, 'render_package_slider']);
        add_shortcode('slotera_package_media', [$this, 'render_package_media']);
        add_shortcode('slotera_package_image', [$this, 'render_package_image']);
        add_shortcode('slotera_package_text_block', [$this, 'render_package_text_block']);
    }


    public function render_contact(array $atts = []): string
    {
        $settings = (new SettingsRepository())->all();
        $security_captcha_provider = (string) ($settings['security_captcha_provider'] ?? 'none');
        $turnstile_site_key = trim((string) ($settings['security_turnstile_site_key'] ?? ''));
        $recaptcha_site_key = trim((string) ($settings['security_recaptcha_site_key'] ?? ''));
        $this->enqueue_contact_captcha_assets($security_captcha_provider, $turnstile_site_key, $recaptcha_site_key);
        $status = isset($_GET['sltr_contact_status']) ? sanitize_key((string) wp_unslash($_GET['sltr_contact_status'])) : '';
        $message = '';
        $message_class = '';

        $contact_locale = $this->detect_contact_locale();
        $contact_labels = $this->contact_form_labels($contact_locale);

        if ($status === 'sent') {
            $message = $contact_labels['sent'];
            $message_class = 'is-success';
        } elseif ($status === 'invalid') {
            $message = $contact_labels['invalid'];
            $message_class = 'is-error';
        } elseif ($status === 'spam') {
            $message = $contact_labels['spam'];
            $message_class = 'is-error';
        } elseif ($status === 'failed') {
            $message = $contact_labels['failed'];
            $message_class = 'is-error';
        }

        $style = $this->contact_form_style_vars($settings);
        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/contact-form.php';
        return (string) ob_get_clean();
    }

    private function enqueue_contact_captcha_assets(string $provider, string $turnstile_site_key, string $recaptcha_site_key): void
    {
        if ($provider === 'turnstile' && $turnstile_site_key !== '') {
            wp_enqueue_script('sltr-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true);
            return;
        }

        if ($provider === 'recaptcha' && $recaptcha_site_key !== '') {
            wp_enqueue_script('sltr-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
            return;
        }

        if ($provider === 'recaptcha_v3' && $recaptcha_site_key !== '') {
            $api_url = add_query_arg('render', $recaptcha_site_key, 'https://www.google.com/recaptcha/api.js');
            wp_enqueue_script('sltr-recaptcha-v3-api', $api_url, [], null, true);
            wp_enqueue_script(
                'sltr-frontend-recaptcha-v3',
                SLTR_PLUGIN_URL . 'assets/js/frontend-recaptcha-v3.js',
                ['sltr-recaptcha-v3-api'],
                SLTR_VERSION,
                true
            );
            wp_localize_script('sltr-frontend-recaptcha-v3', 'sltr_recaptcha_v3', [
                'site_key' => $recaptcha_site_key,
            ]);
        }
    }

    public function handle_contact_form_submit(): void
    {
        $redirect_to = isset($_POST['_wp_http_referer']) ? esc_url_raw((string) wp_unslash($_POST['_wp_http_referer'])) : home_url('/');
        if ($redirect_to === '') {
            $redirect_to = home_url('/');
        }

        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field((string) wp_unslash($_POST['_wpnonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'sltr_contact_form_submit')) {
            $this->redirect_contact($redirect_to, 'invalid');
        }

        $name = isset($_POST['sltr_contact_name']) ? sanitize_text_field((string) wp_unslash($_POST['sltr_contact_name'])) : '';
        $email = isset($_POST['sltr_contact_email']) ? sanitize_email((string) wp_unslash($_POST['sltr_contact_email'])) : '';
        $phone = isset($_POST['sltr_contact_phone']) ? sanitize_text_field((string) wp_unslash($_POST['sltr_contact_phone'])) : '';
        $subject = isset($_POST['sltr_contact_subject']) ? sanitize_text_field((string) wp_unslash($_POST['sltr_contact_subject'])) : '';
        $body = isset($_POST['sltr_contact_message']) ? sanitize_textarea_field((string) wp_unslash($_POST['sltr_contact_message'])) : '';

        if ($name === '' || $email === '' || !is_email($email) || $body === '') {
            $this->redirect_contact($redirect_to, 'invalid');
        }

        $spam_data = [
            'customer_email' => $email,
            'company_website' => isset($_POST['company_website']) ? sanitize_text_field((string) wp_unslash($_POST['company_website'])) : '',
            'form_started_at' => isset($_POST['form_started_at']) ? absint(wp_unslash((string) $_POST['form_started_at'])) : 0,
            'cf_turnstile_response' => isset($_POST['cf-turnstile-response']) ? sanitize_text_field((string) wp_unslash($_POST['cf-turnstile-response'])) : (isset($_POST['cf_turnstile_response']) ? sanitize_text_field((string) wp_unslash($_POST['cf_turnstile_response'])) : ''),
            'g_recaptcha_response' => isset($_POST['g-recaptcha-response']) ? sanitize_text_field((string) wp_unslash($_POST['g-recaptcha-response'])) : (isset($_POST['g_recaptcha_response']) ? sanitize_text_field((string) wp_unslash($_POST['g_recaptcha_response'])) : ''),
        ];
        $security_check = (new BookingSpamProtectionService())->validate_frontend_submission($spam_data, 'contact');
        if ($security_check instanceof WP_Error || is_wp_error($security_check)) {
            $this->redirect_contact($redirect_to, 'spam');
        }

        $settings = (new SettingsRepository())->all();
        $to = sanitize_email((string) ($settings['admin_notification_email'] ?? get_option('admin_email')));
        if ($to === '' || !is_email($to)) {
            $to = get_option('admin_email');
        }

        $contact_locale = isset($_POST['sltr_contact_locale']) ? sanitize_text_field((string) wp_unslash($_POST['sltr_contact_locale'])) : $this->detect_contact_locale();
        $page_url = esc_url_raw($redirect_to);
        $page_title = $this->contact_page_title($page_url);
        $contact_data = [
            'contact_name' => $name,
            'contact_email' => $email,
            'contact_phone' => $phone,
            'contact_subject' => $subject,
            'contact_message' => $body,
            'contact_page_url' => $page_url,
            'contact_page_title' => $page_title,
            'contact_submitted_at' => current_time('mysql'),
            'contact_locale' => $contact_locale,
            'contact_user_ip' => $this->contact_user_ip(),
            'contact_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'site_name' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        ];

        [$mail_subject, $mail_body, $is_html_template] = $this->build_contact_template_email($contact_data);
        $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];
        if ($is_html_template) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }

        $sent = wp_mail($to, $mail_subject, $mail_body, $headers);
        $this->redirect_contact($redirect_to, $sent ? 'sent' : 'failed');
    }

    private function detect_contact_locale(): string
    {
        $accepted = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE'])) : '';
        $supported = $this->contact_supported_locales();
        foreach (explode(',', $accepted) as $part) {
            $code = strtolower(trim((string) explode(';', $part)[0]));
            if ($code === '') {
                continue;
            }
            $normalized = str_replace('-', '_', $code);
            foreach ($supported as $locale) {
                if (strtolower($locale) === $normalized || strtolower(substr($locale, 0, 2)) === substr($normalized, 0, 2)) {
                    return $locale;
                }
            }
        }
        return function_exists('determine_locale') ? (string) determine_locale() : (string) get_locale();
    }

    private function contact_supported_locales(): array
    {
        return ['en_US','de_DE','nl_NL','pl_PL','lt_LT','lv','bg_BG','et','fr_FR','it_IT','es_ES','ru_RU','pt_PT','pt_BR','ro_RO','sv_SE','da_DK','fi','cs_CZ','sk_SK','hu_HU','el','hr','sl_SI','no_NO','nb_NO','is_IS','ga_IE'];
    }

    private function contact_form_labels(string $locale): array
    {
        $language = strtolower(substr(str_replace('_', '-', $locale), 0, 2));
        $labels = [
            'en' => ['name'=>'Name','email'=>'Email','phone'=>'Phone','subject'=>'Message Subject','message'=>'Message','company'=>'Company website','submit'=>'Send Message','sent'=>'Thank you. Your message has been sent.','invalid'=>'Please fill in your name, email address and message.','spam'=>'Message could not be processed. Please try again.','failed'=>'Message could not be sent. Please try again later.'],
            'de' => ['name'=>'Name','email'=>'E-Mail','subject'=>'Betreff','message'=>'Nachricht','company'=>'Firmenwebsite','submit'=>'Nachricht senden','sent'=>'Danke. Ihre Nachricht wurde gesendet.','invalid'=>'Bitte füllen Sie Name, E-Mail-Adresse und Nachricht aus.','spam'=>'Die Nachricht konnte nicht verarbeitet werden. Bitte versuchen Sie es erneut.','failed'=>'Die Nachricht konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.'],
            'fr' => ['name'=>'Nom','email'=>'E-mail','subject'=>'Objet du message','message'=>'Message','company'=>'Site web de l’entreprise','submit'=>'Envoyer le message','sent'=>'Merci. Votre message a été envoyé.','invalid'=>'Veuillez indiquer votre nom, votre adresse e-mail et votre message.','spam'=>'Le message n’a pas pu être traité. Veuillez réessayer.','failed'=>'Le message n’a pas pu être envoyé. Veuillez réessayer plus tard.'],
            'it' => ['name'=>'Nome','email'=>'Email','subject'=>'Oggetto del messaggio','message'=>'Messaggio','company'=>'Sito web aziendale','submit'=>'Invia messaggio','sent'=>'Grazie. Il tuo messaggio è stato inviato.','invalid'=>'Inserisci nome, indirizzo email e messaggio.','spam'=>'Non è stato possibile elaborare il messaggio. Riprova.','failed'=>'Non è stato possibile inviare il messaggio. Riprova più tardi.'],
            'es' => ['name'=>'Nombre','email'=>'Correo electrónico','subject'=>'Asunto del mensaje','message'=>'Mensaje','company'=>'Sitio web de la empresa','submit'=>'Enviar mensaje','sent'=>'Gracias. Tu mensaje ha sido enviado.','invalid'=>'Completa tu nombre, correo electrónico y mensaje.','spam'=>'No se pudo procesar el mensaje. Inténtalo de nuevo.','failed'=>'No se pudo enviar el mensaje. Inténtalo de nuevo más tarde.'],
            'pt' => ['name'=>'Nome','email'=>'Email','subject'=>'Assunto da mensagem','message'=>'Mensagem','company'=>'Site da empresa','submit'=>'Enviar mensagem','sent'=>'Obrigado. A sua mensagem foi enviada.','invalid'=>'Preencha o nome, email e mensagem.','spam'=>'A mensagem não pôde ser processada. Tente novamente.','failed'=>'A mensagem não pôde ser enviada. Tente novamente mais tarde.'],
            'ru' => ['name'=>'Имя','email'=>'Email','phone'=>'Телефон','subject'=>'Тема сообщения','message'=>'Сообщение','company'=>'Сайт компании','submit'=>'Отправить сообщение','sent'=>'Спасибо. Ваше сообщение отправлено.','invalid'=>'Заполните имя, email и сообщение.','spam'=>'Сообщение не удалось обработать. Попробуйте ещё раз.','failed'=>'Сообщение не удалось отправить. Попробуйте позже.'],
            'nl' => ['name'=>'Naam','email'=>'E-mail','subject'=>'Onderwerp','message'=>'Bericht','company'=>'Bedrijfswebsite','submit'=>'Bericht verzenden','sent'=>'Bedankt. Je bericht is verzonden.','invalid'=>'Vul je naam, e-mailadres en bericht in.','spam'=>'Het bericht kon niet worden verwerkt. Probeer het opnieuw.','failed'=>'Het bericht kon niet worden verzonden. Probeer het later opnieuw.'],
            'pl' => ['name'=>'Imię i nazwisko','email'=>'E-mail','subject'=>'Temat wiadomości','message'=>'Wiadomość','company'=>'Strona firmy','submit'=>'Wyślij wiadomość','sent'=>'Dziękujemy. Wiadomość została wysłana.','invalid'=>'Podaj imię i nazwisko, adres e-mail oraz wiadomość.','spam'=>'Nie udało się przetworzyć wiadomości. Spróbuj ponownie.','failed'=>'Nie udało się wysłać wiadomości. Spróbuj ponownie później.'],
            'lt' => ['name'=>'Vardas','email'=>'El. paštas','subject'=>'Žinutės tema','message'=>'Žinutė','company'=>'Įmonės svetainė','submit'=>'Siųsti žinutę','sent'=>'Ačiū. Jūsų žinutė išsiųsta.','invalid'=>'Įveskite vardą, el. pašto adresą ir žinutę.','spam'=>'Žinutės apdoroti nepavyko. Bandykite dar kartą.','failed'=>'Žinutės išsiųsti nepavyko. Bandykite vėliau.'],
            'lv' => ['name'=>'Vārds','email'=>'E-pasts','subject'=>'Ziņojuma temats','message'=>'Ziņojums','company'=>'Uzņēmuma vietne','submit'=>'Sūtīt ziņojumu','sent'=>'Paldies. Jūsu ziņojums ir nosūtīts.','invalid'=>'Lūdzu, aizpildiet vārdu, e-pasta adresi un ziņojumu.','spam'=>'Ziņojumu nevarēja apstrādāt. Lūdzu, mēģiniet vēlreiz.','failed'=>'Ziņojumu nevarēja nosūtīt. Lūdzu, mēģiniet vēlāk.'],
            'bg' => ['name'=>'Име','email'=>'Имейл','subject'=>'Тема на съобщението','message'=>'Съобщение','company'=>'Уебсайт на компания','submit'=>'Изпрати съобщение','sent'=>'Благодарим. Вашето съобщение е изпратено.','invalid'=>'Моля, попълнете име, имейл адрес и съобщение.','spam'=>'Съобщението не можа да бъде обработено. Опитайте отново.','failed'=>'Съобщението не можа да бъде изпратено. Опитайте по-късно.'],
            'et' => ['name'=>'Nimi','email'=>'E-post','phone'=>'Telefon','subject'=>'Sõnumi teema','message'=>'Sõnum','company'=>'Ettevõtte veebisait','submit'=>'Saada sõnum','sent'=>'Aitäh. Sinu sõnum on saadetud.','invalid'=>'Palun täida nimi, e-posti aadress ja sõnum.','spam'=>'Sõnumit ei saanud töödelda. Proovi uuesti.','failed'=>'Sõnumit ei saanud saata. Proovi hiljem uuesti.'],
            'ro' => ['name'=>'Nume','email'=>'Email','subject'=>'Subiectul mesajului','message'=>'Mesaj','company'=>'Site-ul companiei','submit'=>'Trimite mesajul','sent'=>'Mulțumim. Mesajul a fost trimis.','invalid'=>'Completați numele, adresa de email și mesajul.','spam'=>'Mesajul nu a putut fi procesat. Încercați din nou.','failed'=>'Mesajul nu a putut fi trimis. Încercați mai târziu.'],
            'sv' => ['name'=>'Namn','email'=>'E-post','subject'=>'Meddelandets ämne','message'=>'Meddelande','company'=>'Företagets webbplats','submit'=>'Skicka meddelande','sent'=>'Tack. Ditt meddelande har skickats.','invalid'=>'Fyll i namn, e-postadress och meddelande.','spam'=>'Meddelandet kunde inte behandlas. Försök igen.','failed'=>'Meddelandet kunde inte skickas. Försök igen senare.'],
            'da' => ['name'=>'Navn','email'=>'E-mail','subject'=>'Beskedens emne','message'=>'Besked','company'=>'Firmawebsted','submit'=>'Send besked','sent'=>'Tak. Din besked er sendt.','invalid'=>'Udfyld navn, e-mailadresse og besked.','spam'=>'Beskeden kunne ikke behandles. Prøv igen.','failed'=>'Beskeden kunne ikke sendes. Prøv igen senere.'],
            'fi' => ['name'=>'Nimi','email'=>'Sähköposti','subject'=>'Viestin aihe','message'=>'Viesti','company'=>'Yrityksen verkkosivusto','submit'=>'Lähetä viesti','sent'=>'Kiitos. Viestisi on lähetetty.','invalid'=>'Täytä nimi, sähköpostiosoite ja viesti.','spam'=>'Viestiä ei voitu käsitellä. Yritä uudelleen.','failed'=>'Viestiä ei voitu lähettää. Yritä myöhemmin uudelleen.'],
            'cs' => ['name'=>'Jméno','email'=>'E-mail','subject'=>'Předmět zprávy','message'=>'Zpráva','company'=>'Web společnosti','submit'=>'Odeslat zprávu','sent'=>'Děkujeme. Vaše zpráva byla odeslána.','invalid'=>'Vyplňte jméno, e-mailovou adresu a zprávu.','spam'=>'Zprávu se nepodařilo zpracovat. Zkuste to znovu.','failed'=>'Zprávu se nepodařilo odeslat. Zkuste to později.'],
            'sk' => ['name'=>'Meno','email'=>'E-mail','subject'=>'Predmet správy','message'=>'Správa','company'=>'Web spoločnosti','submit'=>'Odoslať správu','sent'=>'Ďakujeme. Vaša správa bola odoslaná.','invalid'=>'Vyplňte meno, e-mailovú adresu a správu.','spam'=>'Správu sa nepodarilo spracovať. Skúste to znova.','failed'=>'Správu sa nepodarilo odoslať. Skúste to neskôr.'],
            'hu' => ['name'=>'Név','email'=>'E-mail','subject'=>'Üzenet tárgya','message'=>'Üzenet','company'=>'Cég weboldala','submit'=>'Üzenet küldése','sent'=>'Köszönjük. Az üzenetet elküldtük.','invalid'=>'Adja meg a nevét, e-mail-címét és üzenetét.','spam'=>'Az üzenetet nem sikerült feldolgozni. Próbálja újra.','failed'=>'Az üzenetet nem sikerült elküldeni. Próbálja meg később.'],
            'el' => ['name'=>'Όνομα','email'=>'Email','subject'=>'Θέμα μηνύματος','message'=>'Μήνυμα','company'=>'Ιστότοπος εταιρείας','submit'=>'Αποστολή μηνύματος','sent'=>'Ευχαριστούμε. Το μήνυμά σας στάλθηκε.','invalid'=>'Συμπληρώστε όνομα, email και μήνυμα.','spam'=>'Δεν ήταν δυνατή η επεξεργασία του μηνύματος. Δοκιμάστε ξανά.','failed'=>'Δεν ήταν δυνατή η αποστολή του μηνύματος. Δοκιμάστε ξανά αργότερα.'],
            'hr' => ['name'=>'Ime','email'=>'Email','subject'=>'Predmet poruke','message'=>'Poruka','company'=>'Web stranica tvrtke','submit'=>'Pošalji poruku','sent'=>'Hvala. Vaša poruka je poslana.','invalid'=>'Unesite ime, email adresu i poruku.','spam'=>'Poruku nije moguće obraditi. Pokušajte ponovno.','failed'=>'Poruku nije moguće poslati. Pokušajte kasnije.'],
            'sl' => ['name'=>'Ime','email'=>'Email','subject'=>'Zadeva sporočila','message'=>'Sporočilo','company'=>'Spletna stran podjetja','submit'=>'Pošlji sporočilo','sent'=>'Hvala. Vaše sporočilo je bilo poslano.','invalid'=>'Vnesite ime, e-poštni naslov in sporočilo.','spam'=>'Sporočila ni bilo mogoče obdelati. Poskusite znova.','failed'=>'Sporočila ni bilo mogoče poslati. Poskusite pozneje.'],
            'no' => ['name'=>'Navn','email'=>'E-post','subject'=>'Meldingsemne','message'=>'Melding','company'=>'Firmas nettsted','submit'=>'Send melding','sent'=>'Takk. Meldingen din er sendt.','invalid'=>'Fyll ut navn, e-postadresse og melding.','spam'=>'Meldingen kunne ikke behandles. Prøv igjen.','failed'=>'Meldingen kunne ikke sendes. Prøv igjen senere.'],
            'nb' => ['name'=>'Navn','email'=>'E-post','subject'=>'Meldingsemne','message'=>'Melding','company'=>'Firmas nettsted','submit'=>'Send melding','sent'=>'Takk. Meldingen din er sendt.','invalid'=>'Fyll ut navn, e-postadresse og melding.','spam'=>'Meldingen kunne ikke behandles. Prøv igjen.','failed'=>'Meldingen kunne ikke sendes. Prøv igjen senere.'],
            'is' => ['name'=>'Nafn','email'=>'Netfang','subject'=>'Efni skilaboða','message'=>'Skilaboð','company'=>'Vefsíða fyrirtækis','submit'=>'Senda skilaboð','sent'=>'Takk. Skilaboðin hafa verið send.','invalid'=>'Fylltu út nafn, netfang og skilaboð.','spam'=>'Ekki var hægt að vinna úr skilaboðunum. Reyndu aftur.','failed'=>'Ekki var hægt að senda skilaboðin. Reyndu aftur síðar.'],
            'ga' => ['name'=>'Ainm','email'=>'Ríomhphost','subject'=>'Ábhar na teachtaireachta','message'=>'Teachtaireacht','company'=>'Suíomh Gréasáin na cuideachta','submit'=>'Seol teachtaireacht','sent'=>'Go raibh maith agat. Seoladh do theachtaireacht.','invalid'=>'Líon isteach d’ainm, do sheoladh ríomhphoist agus do theachtaireacht.','spam'=>'Níorbh fhéidir an teachtaireacht a phróiseáil. Bain triail eile as.','failed'=>'Níorbh fhéidir an teachtaireacht a sheoladh. Bain triail eile as níos déanaí.'],
        ];
        return $labels[$language] ?? $labels['en'];
    }

    private function build_contact_template_email(array $data): array
    {
        $settings = (new SettingsRepository())->all();
        $scenario = 'contact_form_admin';
        $scenarios = \Slotera\Application\Services\EmailTemplateRegistry::scenarios();
        $definition = $scenarios[$scenario] ?? [];
        if ((int) ($settings['email_template_' . $scenario . '_enabled'] ?? 1) !== 1) {
            $subject = '[{site_name}] New contact message';
            $body = "New contact form message.\n\nName: {contact_name}\nEmail: {contact_email}\nPhone: {contact_phone}\nSubject: {contact_subject}\nMessage:\n{contact_message}\n\nPage: {contact_page_title}\nURL: {contact_page_url}\nSubmitted: {contact_submitted_at}\nLocale: {contact_locale}";
            return [$this->replace_contact_placeholders($subject, $data), $this->replace_contact_placeholders($body, $data), false];
        }
        $subject_template = (string) ($settings['email_template_' . $scenario . '_subject'] ?? ($definition['default_subject'] ?? '[{site_name}] New contact message'));
        $use_html = (int) ($settings['email_template_' . $scenario . '_use_html'] ?? 0) === 1;
        $html_body = (string) ($settings['email_template_' . $scenario . '_html_body'] ?? '');
        $plain_body = (string) ($settings['email_template_' . $scenario . '_body'] ?? ($definition['default_body'] ?? 'New contact form message.'));
        $body_template = $use_html && $html_body !== '' ? $html_body : $plain_body;
        return [$this->replace_contact_placeholders($subject_template, $data), $this->replace_contact_placeholders($body_template, $data), $use_html && $html_body !== ''];
    }

    private function replace_contact_placeholders(string $text, array $data): string
    {
        $map = [];
        foreach ($data as $key => $value) {
            $map['{' . $key . '}'] = (string) $value;
        }
        return strtr($text, $map);
    }

    private function contact_page_title(string $url): string
    {
        $post_id = url_to_postid($url);
        if ($post_id > 0) {
            return get_the_title($post_id) ?: '';
        }
        return '';
    }

    private function contact_user_ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $raw = sanitize_text_field((string) wp_unslash($_SERVER[$key]));
                $ip = trim((string) explode(',', $raw)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }

    private function redirect_contact(string $redirect_to, string $status): void
    {
        wp_safe_redirect(add_query_arg('sltr_contact_status', sanitize_key($status), remove_query_arg('sltr_contact_status', $redirect_to)));
        exit;
    }

    private function contact_form_style_vars(array $settings): string
    {
        $appearance_theme = (string) ($settings['appearance_theme'] ?? 'light');
        $vars = [
            '--sltr-form-bg' => '#ffffff',
            '--sltr-form-text' => '#0f172a',
            '--sltr-card-bg' => '#ffffff',
            '--sltr-card-border' => '#dbe3ef',
            '--sltr-primary' => '#2563eb',
            '--sltr-primary-text' => '#ffffff',
            '--sltr-muted' => '#64748b',
        ];

        if ($appearance_theme === 'dark') {
            $vars['--sltr-form-bg'] = '#0f172a';
            $vars['--sltr-form-text'] = '#f8fafc';
            $vars['--sltr-card-bg'] = '#111827';
            $vars['--sltr-card-border'] = '#334155';
            $vars['--sltr-muted'] = '#cbd5e1';
        } elseif ($appearance_theme === 'soft') {
            $vars['--sltr-form-bg'] = '#f8fafc';
            $vars['--sltr-card-bg'] = '#ffffff';
        } elseif ($appearance_theme === 'custom') {
            $map = [
                '--sltr-form-bg' => 'form_background_color',
                '--sltr-form-text' => 'form_text_color',
                '--sltr-card-bg' => 'card_background_color',
                '--sltr-card-border' => 'card_border_color',
                '--sltr-primary' => 'primary_color',
                '--sltr-primary-text' => 'primary_text_color',
                '--sltr-muted' => 'muted_text_color',
            ];
            foreach ($map as $css => $key) {
                $value = sanitize_hex_color((string) ($settings[$key] ?? ''));
                if (is_string($value) && $value !== '') {
                    $vars[$css] = $value;
                }
            }
        }

        $style = '';
        foreach ($vars as $key => $value) {
            $style .= $key . ':' . $value . ';';
        }
        return $style;
    }


    private function booking_paused_notice(): string
    {
        return '<div class="sltr-notice sltr-notice--warning"><strong>'
            . esc_html__('Booking is temporarily unavailable.', 'slotera-booking')
            . '</strong><br>' . esc_html__('Please try again later.', 'slotera-booking') . '</div>';
    }

    public function render_package(array $atts = []): string
    {
        if ((string) (new SettingsRepository())->get('booking_availability_status', 'available') === 'paused') { return $this->booking_paused_notice(); }
        $atts = shortcode_atts(['package_id' => 0], $atts, 'slotera_booking');
        $repo = new PackageRepository();
        $package_id = absint((string) $atts['package_id']);
        $package_id_from_query = $this->request->get_int('sltr_package_id');

        if ($package_id <= 0 && $package_id_from_query > 0) {
            $package_id = $package_id_from_query;
        }

        if ($package_id > 0) {
            $package = $repo->get_by_id($package_id);

            if ($package && !empty($package['is_active']) && $package_id_from_query <= 0) {
                ob_start();
                include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/package-detail.php';
                return (string) ob_get_clean();
            }

            $packages = ($package && !empty($package['is_active'])) ? [$package] : [];
        } else {
            $packages = $repo->get_active(100, 0);
        }

        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/booking-form.php';
        return (string) ob_get_clean();
    }

    public function render_category(array $atts = []): string
    {
        if ((string) (new SettingsRepository())->get('booking_availability_status', 'available') === 'paused') { return $this->booking_paused_notice(); }
        $atts = shortcode_atts(['category_id' => 0], $atts, 'slotera_category');
        $category_id = absint((string) $atts['category_id']);
        $category = $category_id > 0 ? (new \Slotera\Infrastructure\Repositories\CategoryRepository())->get_by_id($category_id) : null;
        $packages = $category_id > 0 ? (new PackageRepository())->get_active_by_category($category_id, 100, 0) : [];

        ob_start();
        if (is_array($category) && class_exists('Slotera\\Application\\Services\\BreadcrumbService')) {
            (new \Slotera\Application\Services\BreadcrumbService())->render_category($category);
        }
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/booking-form.php';
        return (string) ob_get_clean();
    }

    
    public function render_categories(array $atts = []): string
    {
        $categories = (new \Slotera\Infrastructure\Repositories\CategoryRepository())->get_active();

        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/categories-list.php';
        return (string) ob_get_clean();
    }

public function render_packages(array $atts = []): string
    {
        $packages = (new PackageRepository())->get_active(100, 0);

        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/packages-list.php';
        return (string) ob_get_clean();
    }

    public function render_thank_you(array $atts = []): string
    {
        $booking_id = $this->request->get_int('booking_id');
        $booking = null;
        $package = null;
        $access_denied = false;
        $booking_access_verified = false;

        if ($booking_id > 0) {
            $candidate = (new BookingRepository())->get_by_id($booking_id);

            if ($candidate && (new BookingAccessTokenService())->has_session($candidate)) {
                $booking_access_verified = true;
                $booking = $candidate;

                if (!empty($booking['package_id'])) {
                    $package = (new PackageRepository())->get_by_id((int) $booking['package_id']);
                }
            } else {
                $access_denied = true;
                $booking = null;
                $package = null;
            }
        }

        $settings = (new SettingsRepository())->all();
        $theme = sanitize_key((string) ($settings['appearance_theme'] ?? 'light'));
        if (!in_array($theme, ['light', 'dark', 'soft', 'minimal', 'custom'], true)) {
            $theme = 'light';
        }
        $style_vars = $theme === 'custom' ? $this->contact_form_style_vars($settings) : '';

        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/thank-you.php';
        return (string) ob_get_clean();
    }


    public function render_checkout(array $atts = []): string
    {
        if ((string) (new SettingsRepository())->get('booking_availability_status', 'available') === 'paused') { return $this->booking_paused_notice(); }
        $booking_id = $this->request->get_int('booking_id');
        $booking = null;
        $package = null;
        $access_denied = false;
        $booking_access_verified = false;

        if ($booking_id > 0) {
            $candidate = (new BookingRepository())->get_by_id($booking_id);
            if ($candidate && (new BookingAccessTokenService())->has_session($candidate)) {
                $booking_access_verified = true;
                $booking = $candidate;
                if (!empty($booking['package_id'])) {
                    $package = (new PackageRepository())->get_by_id((int) $booking['package_id']);
                }
            } else {
                $access_denied = true;
            }
        }

        $settings = (new SettingsRepository())->all();
        $theme = sanitize_key((string) ($settings['appearance_theme'] ?? 'light'));
        if (!in_array($theme, ['light', 'dark', 'soft', 'minimal', 'custom'], true)) {
            $theme = 'light';
        }
        $style_vars = $theme === 'custom' ? $this->contact_form_style_vars($settings) : '';

        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/checkout.php';
        return (string) ob_get_clean();
    }

    public function render_login(array $atts = []): string
    {
        $account_service = new AccountMagicLinkService();
        $account_url = $account_service->account_url();
        $is_logged_in = $account_service->is_logged_in();
        $settings = (new SettingsRepository())->all();
        $theme = (string) ($settings['appearance_theme'] ?? 'light');
        $style_vars = $this->contact_form_style_vars($settings);
        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/login.php';
        return (string) ob_get_clean();
    }

    public function render_account(array $atts = []): string
    {
        $account_service = new AccountMagicLinkService();
        $customer_email = $account_service->current_email();
        $is_logged_in = $customer_email !== '';
        $login_url = $account_service->login_url();
        $settings = (new SettingsRepository())->all();
        $theme = (string) ($settings['appearance_theme'] ?? 'light');
        $style_vars = $this->contact_form_style_vars($settings);
        $invoice_pdf_enabled = (int) ($settings['invoice_pdf_enabled'] ?? 1) === 1;
        $bookings = $is_logged_in ? (new BookingRepository())->get_by_customer_email($customer_email, 100, 0) : [];
        $packages_by_id = [];
        $package_repo = new PackageRepository();
        $history_repo = new BookingHistoryRepository();
        $today = current_time('Y-m-d');
        $upcoming_bookings = [];
        $past_bookings = [];
        $selected_booking = null;
        $selected_history = [];
        $selected_id = isset($_GET['sltr_booking']) ? absint(wp_unslash((string) $_GET['sltr_booking'])) : 0;

        foreach ($bookings as $booking) {
            $pid = (int) ($booking['package_id'] ?? 0);
            if ($pid > 0 && !isset($packages_by_id[$pid])) {
                $packages_by_id[$pid] = $package_repo->get_by_id($pid);
            }
            $status = sanitize_key((string) ($booking['status'] ?? ''));
            $booking_date = (string) ($booking['booking_date'] ?? '');
            if ((int) ($booking['id'] ?? 0) === $selected_id) {
                $selected_booking = $booking;
                $selected_history = $history_repo->get_by_booking($selected_id, 20);
            }
            if ($status === 'completed' || $status === 'cancelled' || ($booking_date !== '' && $booking_date < $today)) {
                $past_bookings[] = $booking;
            } else {
                $upcoming_bookings[] = $booking;
            }
        }
        ob_start();
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/account.php';
        return (string) ob_get_clean();
    }

    public function render_package_media(array $atts = []): string
    {
        return $this->render_package_slider($atts, 'slotera_package_media');
    }

    public function render_package_slider(array $atts = [], string $shortcode = 'slotera_package_slider'): string
    {
        $package = $this->resolve_shortcode_package($atts, $shortcode);
        if (!$package) {
            return '';
        }

        $media_id = sanitize_key((string) ($atts['id'] ?? ''));
        $media = [];
        if ($shortcode === 'slotera_package_media' && $media_id !== '') {
            $all_media = json_decode((string) ($package['solo_media_json'] ?? '{}'), true);
            $all_media = is_array($all_media) ? $all_media : [];
            $media = is_array($all_media[$media_id] ?? null) ? $all_media[$media_id] : [];
        }

        $media_type = (string) ($media['type'] ?? 'images');
        if ($shortcode === 'slotera_package_media' && $media_type === 'video') {
            $video_id = absint((string) ($media['video_id'] ?? 0));
            $video_url = $video_id > 0 ? wp_get_attachment_url($video_id) : '';
            $video_mime = $video_id > 0 ? (string) get_post_mime_type($video_id) : '';
            if ($video_url && in_array(strtolower($video_mime), ['video/mp4', 'video/webm', 'video/ogg'], true)) {
                $video_autoplay = !empty($media['autoplay']);
                ob_start();
                ?>
                <div class="sltr-package-media-video">
                    <video controls playsinline preload="metadata" <?php echo $video_autoplay ? 'autoplay muted' : ''; ?>>
                        <source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr($video_mime); ?>">
                        <?php esc_html_e('Your browser does not support this video.', 'slotera-booking'); ?>
                    </video>
                    <?php if ($video_autoplay) : ?><button type="button" class="sltr-package-video-unmute" data-sltr-video-unmute aria-label="<?php esc_attr_e('Turn on sound', 'slotera-booking'); ?>"><span aria-hidden="true">🔊</span><span><?php esc_html_e('Turn on sound', 'slotera-booking'); ?></span></button><?php endif; ?>
                </div>
                <?php
                return (string) ob_get_clean();
            }
            return '';
        }

        $ids = $this->parse_image_ids((string) ($media['ids'] ?? ($package['slider_image_ids'] ?? '')));
        if (!$ids) { return ''; }

        $speed = max(1000, min(30000, (int) ($media['speed'] ?? ($package['slider_speed'] ?? 4000))));
        $settings = (new SettingsRepository())->all();
        $fit_mode = (string) ($package['media_fit_mode'] ?? ($settings['media_fit_mode'] ?? 'cover'));
        $fit_class = $fit_mode === 'contain' ? ' sltr-media-fit-contain' : ' sltr-media-fit-cover';
        $position_class = ' sltr-slider-position-top';
        $focus_map = json_decode((string) ($media['focus'] ?? ($package['slider_image_focus_json'] ?? '{}')), true);
        $focus_map = is_array($focus_map) ? $focus_map : [];
        $slides = [];
        foreach ($ids as $id) {
            $large = wp_get_attachment_image_url($id, 'large');
            if (!$large) {
                $large = wp_get_attachment_image_url($id, 'full');
            }
            $full = wp_get_attachment_image_url($id, 'full');
            if (!$large || !$full) {
                continue;
            }
            $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
            $slides[] = [
                'large' => $large,
                'full' => $full,
                'alt' => is_string($alt) ? $alt : '',
                'focus' => (string) ($focus_map[(string) $id] ?? '50,50'),
            ];
        }

        if (!$slides) {
            return '';
        }

        ob_start();
        ?>
        <div class="sltr-package-slider<?php echo count($slides) === 1 ? ' sltr-package-media-single' : ''; ?><?php echo esc_attr($fit_class . $position_class); ?>" data-speed="<?php echo esc_attr((string) $speed); ?>">
            <div class="sltr-package-slider-track">
                <?php foreach ($slides as $index => $slide) : ?>
                    <button type="button" class="sltr-package-slider-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" data-full="<?php echo esc_url($slide['full']); ?>" aria-label="<?php esc_attr_e('Open image preview', 'slotera-booking'); ?>">
                        <?php
                        $fp = array_map('intval', explode(',', (string) $slide['focus']));
                        $focus_x = max(0, min(100, $fp[0] ?? 50));
                        $focus_y = max(0, min(100, $fp[1] ?? 50));
                        $focus = $focus_x . '% ' . $focus_y . '%';
                        ?>
                        <img src="<?php echo esc_url($slide['large']); ?>" alt="<?php echo esc_attr($slide['alt']); ?>" loading="lazy" data-focus-x="<?php echo esc_attr((string) $focus_x); ?>" data-focus-y="<?php echo esc_attr((string) $focus_y); ?>" style="<?php echo esc_attr('object-position:' . $focus . ' !important;'); ?>">
                        <span class="sltr-media-zoom-icon" aria-hidden="true">⌕</span>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php if (count($slides) > 1) : ?>
                <div class="sltr-package-slider-controls" aria-label="<?php esc_attr_e('Slider controls', 'slotera-booking'); ?>">
                    <button type="button" class="sltr-package-slider-arrow sltr-package-slider-arrow-prev" data-sltr-slider-direction="prev" aria-label="<?php esc_attr_e('Previous image', 'slotera-booking'); ?>">
                        <span aria-hidden="true">‹</span>
                    </button>
                    <div class="sltr-package-slider-dots" aria-label="<?php esc_attr_e('Slider navigation', 'slotera-booking'); ?>">
                        <?php foreach ($slides as $index => $_slide) : ?>
                            <button type="button" class="sltr-package-slider-dot<?php echo $index === 0 ? ' is-active' : ''; ?>" data-slide="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr(sprintf(sltr_t('Show slide %d'), $index + 1)); ?>" onclick="return window.sltrPackageSliderGo ? window.sltrPackageSliderGo(this, <?php echo esc_attr((string) $index); ?>) : false;"></button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="sltr-package-slider-arrow sltr-package-slider-arrow-next" data-sltr-slider-direction="next" aria-label="<?php esc_attr_e('Next image', 'slotera-booking'); ?>">
                        <span aria-hidden="true">›</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_package_image(array $atts = [], string $shortcode = 'slotera_package_image'): string
    {
        $package = $this->resolve_shortcode_package($atts, $shortcode);
        if (!$package) {
            return '';
        }

        $ids = array_slice($this->parse_image_ids((string) ($package['gallery_image_ids'] ?? '')), 0, 1);
        if (!$ids) {
            return '';
        }

        $settings = (new SettingsRepository())->all();
        $fit_mode = (string) ($package['media_fit_mode'] ?? ($settings['media_fit_mode'] ?? 'cover'));
        $fit_class = $fit_mode === 'contain' ? ' sltr-media-fit-contain' : ' sltr-media-fit-cover';

        $gallery_focus = (string) ($package['gallery_image_focus'] ?? '50,50');
        $items = [];
        foreach ($ids as $id) {
            $large = wp_get_attachment_image_url($id, 'slotera_gallery');
            if (!$large) {
                $large = wp_get_attachment_image_url($id, 'large');
            }
            $full = wp_get_attachment_image_url($id, 'full');
            if (!$large || !$full) {
                continue;
            }
            $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
            $items[] = [
                'large' => $large,
                'full' => $full,
                'alt' => is_string($alt) ? $alt : '',
                'focus' => $gallery_focus,
            ];
        }

        if (!$items) {
            return '';
        }

        $count = count($items);
        $layout = 'single';
        ob_start();
        ?>
        <div class="sltr-package-gallery sltr-package-image<?php echo esc_attr($fit_class); ?> sltr-package-gallery-<?php echo esc_attr($layout); ?> sltr-package-gallery-count-<?php echo esc_attr((string) $count); ?>">
            <?php foreach ($items as $item) : ?>
                <button type="button" class="sltr-package-gallery-item" data-full="<?php echo esc_url($item['full']); ?>" aria-label="<?php esc_attr_e('Open image preview', 'slotera-booking'); ?>">
                    <img src="<?php echo esc_url($item['large']); ?>" alt="<?php echo esc_attr($item['alt']); ?>" loading="lazy" style="--sltr-image-focus:<?php $fp=array_map('intval',explode(',',$item['focus'])); echo esc_attr(max(0,min(100,$fp[0]??50)).'% '.max(0,min(100,$fp[1]??50)).'%'); ?>">
                    <span class="sltr-media-zoom-icon" aria-hidden="true">⌕</span>
                </button>
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_package_text_block(array $atts = []): string
    {
        $package = $this->resolve_shortcode_package($atts, 'slotera_package_text_block');
        if (!$package) {
            return '';
        }

        $title = trim((string) ($package['right_block_title'] ?? ''));
        $text = trim((string) ($package['right_block_text'] ?? ''));
        if ($title === '' && $text === '') {
            return '';
        }

        $title_font_family = trim((string) ($package['right_block_title_font_family'] ?? ($package['right_block_font_family'] ?? '')));
        $title_font_size = max(12, min(48, (int) ($package['right_block_title_font_size'] ?? 32)));
        $text_font_family = trim((string) ($package['right_block_text_font_family'] ?? ($package['right_block_font_family'] ?? 'Inter, Arial, sans-serif')));
        $text_font_size = max(12, min(48, (int) ($package['right_block_text_font_size'] ?? ($package['right_block_font_size'] ?? 24))));
        $title_style = sprintf(
            'font-family:%s;font-size:%dpx;',
            $title_font_family !== '' ? $title_font_family : 'inherit',
            $title_font_size
        );
        $text_style = sprintf(
            'font-family:%s;font-size:%dpx;',
            $text_font_family !== '' ? $text_font_family : 'inherit',
            $text_font_size
        );

        ob_start();
        ?>
        <div class="sltr-package-text-block">
            <?php if ($title !== '') : ?><h2 style="<?php echo esc_attr($title_style); ?>"><?php echo esc_html($title); ?></h2><?php endif; ?>
            <?php if ($text !== '') : ?><div class="sltr-package-text-block-body" style="<?php echo esc_attr($text_style); ?>"><?php echo HtmlSanitizer::render_public_content($text, true, false); ?></div><?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function resolve_shortcode_package(array $atts, string $shortcode): ?array
    {
        $atts = shortcode_atts(['package_id' => 0, 'id' => 0], $atts, $shortcode);
        $package_id = absint((string) ($atts['package_id'] ?: $atts['id']));

        if ($package_id <= 0 && !empty($GLOBALS['sltr_current_package']['id'])) {
            $package_id = (int) $GLOBALS['sltr_current_package']['id'];
        }

        if ($package_id <= 0) {
            return null;
        }

        $package = (new PackageRepository())->get_by_id($package_id);
        return ($package && !empty($package['is_active'])) ? $package : null;
    }

    private function parse_image_ids(string $ids): array
    {
        $values = array_filter(array_map('absint', preg_split('/[\s,]+/', $ids) ?: []));
        return array_values(array_unique($values));
    }
    public function render(array $atts = []): string
    {
        return $this->render_package($atts);
    }
}
