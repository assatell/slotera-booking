<?php if (!defined('ABSPATH')) { exit; } ?>
<?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/booking-blocks/simple.php')) { require $sltr_view; } ?>
<?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/booking-blocks/fixed.php')) { require $sltr_view; } ?>
<?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/booking-blocks/flex.php')) { require $sltr_view; } ?>
<?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/booking-blocks/date-range-inventory.php')) { require $sltr_view; } ?>
