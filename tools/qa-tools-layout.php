<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$tools = file_get_contents($root . '/includes/Admin/Pages/ToolsPage.php');
$css = file_get_contents($root . '/assets/css/admin.css');

$errors = [];
foreach (['1. Maintenance', '3. Rebuild data', '4. Export / Import', '5. System status'] as $legacy) {
    if (strpos($tools, $legacy) !== false) {
        $errors[] = 'Legacy numbered Tools heading remains: ' . $legacy;
    }
}
foreach (['sltr-tools-layout', 'sltr-tools-layout__status', 'sltr-tools-layout__actions'] as $class) {
    if (strpos($tools, $class) === false) {
        $errors[] = 'Missing Tools layout class: ' . $class;
    }
}
$systemPos = strpos($tools, '$this->render_status();');
$maintenancePos = strpos($tools, '$this->render_maintenance();');
if ($systemPos === false || $maintenancePos === false || $systemPos > $maintenancePos) {
    $errors[] = 'System status is not rendered before the right-column actions.';
}
if (strpos($css, '.sltr-tools-layout') === false || strpos($css, 'grid-template-columns: minmax(0, 1fr) minmax(360px, 1fr)') === false) {
    $errors[] = 'Two-column Tools CSS is missing.';
}
if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}
echo "Tools layout regression: OK\n";
