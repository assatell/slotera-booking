<?php
if (!defined('ABSPATH')) { exit; }

$settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
$theme = sanitize_key((string) ($settings['appearance_theme'] ?? 'light'));
$colors = [
    'form_bg' => '#ffffff',
    'form_text' => '#0f172a',
    'card_bg' => '#ffffff',
    'card_border' => '#dbe3ef',
    'primary' => '#2563eb',
    'primary_text' => '#ffffff',
    'muted' => '#64748b',
];

if ($theme === 'dark') {
    $colors['form_bg'] = '#0f172a';
    $colors['form_text'] = '#f8fafc';
    $colors['card_bg'] = '#111827';
    $colors['card_border'] = '#334155';
    $colors['muted'] = '#cbd5e1';
} elseif ($theme === 'soft') {
    $colors['form_bg'] = '#f8fafc';
    $colors['card_bg'] = '#ffffff';
} elseif ($theme === 'custom') {
    $map = [
        'form_bg' => 'form_background_color',
        'form_text' => 'form_text_color',
        'card_bg' => 'card_background_color',
        'card_border' => 'card_border_color',
        'primary' => 'primary_color',
        'primary_text' => 'primary_text_color',
        'muted' => 'muted_text_color',
    ];
    foreach ($map as $target => $key) {
        $value = sanitize_hex_color((string) ($settings[$key] ?? ''));
        if (is_string($value) && $value !== '') {
            $colors[$target] = $value;
        }
    }
}
?>
<style>
:root{--sltr-form-bg:<?php echo esc_html($colors['form_bg']); ?>;--sltr-form-text:<?php echo esc_html($colors['form_text']); ?>;--sltr-card-bg:<?php echo esc_html($colors['card_bg']); ?>;--sltr-card-border:<?php echo esc_html($colors['card_border']); ?>;--sltr-primary:<?php echo esc_html($colors['primary']); ?>;--sltr-primary-text:<?php echo esc_html($colors['primary_text']); ?>;--sltr-muted:<?php echo esc_html($colors['muted']); ?>}
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:var(--sltr-form-bg);margin:0;padding:40px;color:var(--sltr-form-text)}.sltr-confirm{max-width:640px;margin:0 auto;background:var(--sltr-card-bg);color:var(--sltr-form-text);border:1px solid var(--sltr-card-border);border-radius:12px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.12)}.sltr-confirm h1{margin-top:0;color:var(--sltr-form-text)}.sltr-confirm p,.sltr-confirm label,.sltr-confirm legend{color:var(--sltr-form-text)}.sltr-confirm a:not(.button){color:var(--sltr-primary)}.sltr-summary{background:var(--sltr-form-bg);color:var(--sltr-form-text);border:1px solid var(--sltr-card-border);border-radius:8px;padding:12px}.sltr-error{background:var(--sltr-card-bg);color:var(--sltr-form-text);border:1px solid var(--sltr-primary);border-radius:8px;margin:16px 0;padding:12px}.button{display:inline-flex;align-items:center;justify-content:center;min-width:170px;padding:10px 22px;border-radius:6px;border:1px solid var(--sltr-primary);background:var(--sltr-primary);color:var(--sltr-primary-text);text-decoration:none;cursor:pointer}.button-secondary{background:var(--sltr-card-bg);color:var(--sltr-form-text);border-color:var(--sltr-card-border);margin-left:8px}.sltr-date-form,.sltr-slots-form{margin:20px 0}.sltr-date-form label{display:block;font-weight:600;margin-bottom:8px}.sltr-date-picker{position:relative;display:flex;max-width:320px;margin-bottom:12px}.sltr-date-picker input[type=text]{width:100%;min-height:40px;padding:6px 46px 6px 10px;border:1px solid var(--sltr-card-border);border-radius:6px;background:var(--sltr-card-bg);color:var(--sltr-form-text)}.sltr-date-picker input[type=text]::placeholder{color:var(--sltr-muted)}.sltr-calendar-button{position:absolute;right:1px;top:1px;bottom:1px;width:42px;border:0;border-left:1px solid var(--sltr-card-border);border-radius:0 5px 5px 0;background:var(--sltr-card-bg);color:var(--sltr-form-text);cursor:pointer;font-size:20px}.sltr-calendar-button:hover,.sltr-calendar-button:focus{background:var(--sltr-form-bg)}.sltr-native-date{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}.sltr-slots{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:12px 0 18px}.sltr-slots label{display:block;border:1px solid var(--sltr-card-border);border-radius:8px;padding:10px;background:var(--sltr-card-bg);color:var(--sltr-form-text);cursor:pointer}.sltr-slots label:has(input:checked){border-color:var(--sltr-primary);box-shadow:0 0 0 1px var(--sltr-primary)}.sltr-confirm fieldset{border-color:var(--sltr-card-border)}
@media (max-width:520px){body{padding:20px 14px}.sltr-confirm{padding:20px 16px}.sltr-confirm form{display:flex;flex-direction:column;gap:10px}.sltr-confirm .button{width:100%;min-width:0}.sltr-confirm .button-secondary{margin-left:0}}
</style>
