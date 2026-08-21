<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$customers = file_get_contents($root . '/includes/Admin/Views/customers.php');
$bookings = file_get_contents($root . '/includes/Admin/Views/bookings.php');
$header = file_get_contents($root . '/includes/Admin/Views/seo-settings/header.php');

$errors = [];
foreach (['class="sltr-filters"', "placeholder=\"<?php esc_attr_e('Name, email or phone', 'slotera-booking'); ?>\"", "button button-primary", "esc_html_e('Apply'", "esc_html_e('Reset'"] as $needle) {
    if (strpos($customers, $needle) === false) {
        $errors[] = 'Customers search is missing Bookings pattern fragment: ' . $needle;
    }
}
if (strpos($customers, 'class="search-form"') !== false || strpos($customers, 'search-box') !== false) {
    $errors[] = 'Legacy Customers search markup remains.';
}
if (strpos($header, 'Manage Slotera SEO, package/category SEO and optional WordPress page SEO from one place.') !== false) {
    $errors[] = 'Global SEO description returned.';
}
if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}
echo "Customers filter UI regression: OK\n";
