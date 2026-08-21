<?php
if (!defined('ABSPATH')) { exit; }
$slots = isset($slots) && is_array($slots) ? $slots : [];
$full_day_booking = !empty($full_day_booking);
$available_dates = isset($available_dates) && is_array($available_dates) ? $available_dates : [];
$display_start_time_only = !empty($display_start_time_only);
$format_time = static function (string $time): string {
    return preg_match('/^\d{2}:\d{2}/', $time) ? substr($time, 0, 5) : $time;
};
?>
<!doctype html>
<html lang="<?php echo esc_attr((string) ($html_lang ?? 'en-US')); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html((string) ($title ?? sltr_t('Reschedule booking', 'frontend', (string) ($locale ?? 'en_US')))); ?></title>
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body>
<main class="sltr-confirm">
    <h1><?php echo esc_html((string) ($heading ?? sltr_t('Reschedule booking', 'frontend', (string) ($locale ?? 'en_US')))); ?></h1>
    <?php if (!empty($summary)) : ?>
        <p class="sltr-summary"><?php echo esc_html((string) $summary); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <div class="sltr-error" role="alert"><?php echo esc_html((string) $error); ?></div>
    <?php endif; ?>

    <?php if ($full_day_booking) : ?>
        <?php if (!empty($available_dates)) : ?>
            <form method="post" action="<?php echo esc_url((string) ($base_url ?? home_url('/'))); ?>" class="sltr-date-form">
                <?php echo $nonce_field ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress-generated nonce field. ?>
                <input type="hidden" name="sltr_token" value="<?php echo esc_attr((string) ($token ?? '')); ?>">
                <?php if (($return_context ?? '') === 'account') : ?><input type="hidden" name="sltr_return" value="account"><?php endif; ?>
                <label for="sltr-full-day-new-date"><?php echo esc_html(sltr_t('Choose a new date', 'frontend', (string) ($locale ?? 'en_US'))); ?></label>
                <select id="sltr-full-day-new-date" name="sltr_date" required>
                    <option value=""><?php echo esc_html(sltr_t('Available dates', 'frontend', (string) ($locale ?? 'en_US'))); ?></option>
                    <?php foreach ($available_dates as $available_date) : ?>
                        <option value="<?php echo esc_attr((string) $available_date); ?>"><?php echo esc_html(sltr_format_localized_date((string) $available_date, (string) ($locale ?? 'en_US'))); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit"><?php echo esc_html(sltr_t('Confirm reschedule', 'frontend', (string) ($locale ?? 'en_US'))); ?></button>
            </form>
        <?php else : ?>
            <p class="sltr-error"><?php echo esc_html(sltr_t('No available dates', 'frontend', (string) ($locale ?? 'en_US'))); ?></p>
        <?php endif; ?>
    <?php else : ?>
        <form method="get" action="<?php echo esc_url((string) ($home_url ?? home_url('/'))); ?>" class="sltr-date-form">
            <input type="hidden" name="sltr_action" value="reschedule_booking">
            <input type="hidden" name="sltr_token" value="<?php echo esc_attr((string) ($token ?? '')); ?>">
            <?php if (($return_context ?? '') === 'account') : ?><input type="hidden" name="sltr_return" value="account"><?php endif; ?>
            <label for="sltr-date"><?php echo esc_html(sltr_t('Choose a new date', 'frontend', (string) ($locale ?? 'en_US'))); ?></label>
            <div class="sltr-date-picker" data-date-order="<?php echo esc_attr((string) ($date_order ?? 'dmy')); ?>">
                <input id="sltr-date" type="text" name="sltr_date" inputmode="numeric" autocomplete="off" placeholder="<?php echo esc_attr((string) ($date_placeholder ?? 'dd/mm/yyyy')); ?>" value="<?php echo esc_attr((string) ($selected_date_display ?? '')); ?>" required>
                <button type="button" class="sltr-calendar-button" aria-label="<?php echo esc_attr(sltr_t('Choose a new date', 'frontend', (string) ($locale ?? 'en_US'))); ?>" title="<?php echo esc_attr(sltr_t('Choose a new date', 'frontend', (string) ($locale ?? 'en_US'))); ?>">&#128197;</button>
                <input id="sltr-native-date" class="sltr-native-date" type="date" tabindex="-1" aria-hidden="true" value="<?php echo esc_attr((string) ($selected_date ?? '')); ?>">
            </div>
            <button class="button" type="submit"><?php echo esc_html(sltr_t('Find available times', 'frontend', (string) ($locale ?? 'en_US'))); ?></button>
        </form>

        <?php if (!empty($selected_date) && !empty($slots)) : ?>
            <form method="post" action="<?php echo esc_url((string) ($base_url ?? home_url('/'))); ?>" class="sltr-slots-form" data-sltr-rest-url="<?php echo esc_attr((string) ($rest_url ?? '')); ?>" data-sltr-rest-nonce="<?php echo esc_attr((string) ($rest_nonce ?? '')); ?>">
                <?php echo $nonce_field ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress-generated nonce field. ?>
                <input type="hidden" name="sltr_token" value="<?php echo esc_attr((string) ($token ?? '')); ?>">
                <?php if (($return_context ?? '') === 'account') : ?><input type="hidden" name="sltr_return" value="account"><?php endif; ?>
                <input type="hidden" name="sltr_date" value="<?php echo esc_attr((string) $selected_date); ?>">
                <fieldset>
                    <legend><?php echo esc_html(sltr_t('Available times', 'frontend', (string) ($locale ?? 'en_US'))); ?></legend>
                    <div class="sltr-slots">
                        <?php foreach ($slots as $slot) : ?>
                            <?php
                            $start = (string) ($slot['start'] ?? '');
                            $end = (string) ($slot['end'] ?? '');
                            if ($start === '' || $end === '') { continue; }
                            $value = $start . '|' . $end;
                            $label = $display_start_time_only
                                ? $format_time($start)
                                : $format_time($start) . ' - ' . $format_time($end);
                            ?>
                            <label><input type="radio" name="sltr_slot" value="<?php echo esc_attr($value); ?>" required> <span><?php echo esc_html($label); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <button class="button" type="submit"><?php echo esc_html(sltr_t('Confirm new time', 'frontend', (string) ($locale ?? 'en_US'))); ?></button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <p><a href="<?php echo esc_url((string) ($home_url ?? home_url('/'))); ?>"><?php echo esc_html(($return_context ?? '') === 'account' ? sltr_t('Back to client account', 'frontend', (string) ($locale ?? 'en_US')) : sltr_t('Back to site', 'frontend', (string) ($locale ?? 'en_US'))); ?></a></p>
</main>

<script>
(function(){
  var wrap=document.querySelector('.sltr-date-picker'); if(!wrap)return;
  var text=document.getElementById('sltr-date'), native=document.getElementById('sltr-native-date'), button=wrap.querySelector('.sltr-calendar-button');
  var order=wrap.getAttribute('data-date-order')||'dmy';
  function localize(iso){var m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(iso||'');if(!m)return '';return order==='mdy'?m[2]+'/'+m[3]+'/'+m[1]:(order==='ymd'?m[1]+'/'+m[2]+'/'+m[3]:m[3]+'/'+m[2]+'/'+m[1]);}
  function openPicker(){try{if(native.showPicker){native.showPicker();}else{native.click();}}catch(e){native.click();}}
  button.addEventListener('click',openPicker);
  native.addEventListener('change',function(){if(native.value){text.value=localize(native.value);text.dispatchEvent(new Event('change',{bubbles:true}));}});
})();
</script>
</body>
</html>
