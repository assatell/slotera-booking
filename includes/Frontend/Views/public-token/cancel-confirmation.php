<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html lang="<?php echo esc_attr((string) ($html_lang ?? 'en-US')); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html((string) ($title ?? sltr_t('Confirm cancellation', 'frontend', (string) ($locale ?? 'en_US')))); ?></title>
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body>
<main class="sltr-confirm">
    <h1><?php echo esc_html((string) ($heading ?? sltr_t('Cancel booking?', 'frontend', (string) ($locale ?? 'en_US')))); ?></h1>
    <p><?php echo esc_html((string) ($message ?? '')); ?></p>
    <?php if (!empty($summary)) : ?>
        <p class="sltr-summary"><?php echo nl2br(esc_html((string) $summary)); ?></p>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url((string) ($action_url ?? home_url('/'))); ?>" data-sltr-rest-url="<?php echo esc_attr((string) ($rest_url ?? '')); ?>" data-sltr-rest-nonce="<?php echo esc_attr((string) ($rest_nonce ?? '')); ?>">
        <?php echo $nonce_field ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress-generated nonce field. ?>
        <input type="hidden" name="sltr_token" value="<?php echo esc_attr((string) ($token ?? '')); ?>">
        <?php if (($return_context ?? '') === 'account') : ?><input type="hidden" name="sltr_return" value="account"><?php endif; ?>
        <button class="button" type="submit"><?php echo esc_html((string) ($cancel_label ?? sltr_t('Yes, cancel booking', 'frontend', (string) ($locale ?? 'en_US')))); ?></button>
        <a class="button button-secondary" href="<?php echo esc_url((string) ($home_url ?? home_url('/'))); ?>"><?php echo esc_html((string) ($keep_label ?? sltr_t('Keep booking', 'frontend', (string) ($locale ?? 'en_US')))); ?></a>
    </form>
</main>
</body>
</html>
