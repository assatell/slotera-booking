<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html((string) ($title ?? sltr_t('Booking link'))); ?></title>
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body>
<main class="sltr-confirm">
    <h1><?php echo esc_html((string) ($heading ?? $title ?? sltr_t('Booking link'))); ?></h1>
    <p><?php echo esc_html((string) ($message ?? '')); ?></p>
    <p><a class="button" href="<?php echo esc_url((string) ($home_url ?? home_url('/'))); ?>"><?php echo esc_html((string) ($button_label ?? sltr_t('Back to site'))); ?></a></p>
</main>
</body>
</html>
