<?php
/**
 * Plugin Name: Slotera Booking
 * Description: Complete WordPress booking platform with payments, packages, coupons, marketing automation, analytics and customer management.
 * Version: 1.0.1046
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: slotera-booking
 * Domain Path: /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('SLTR_VERSION', '1.0.1046');
define('SLTR_UPDATE_URI', '');
define('SLTR_MINIMUM_WP_VERSION', '6.0');
define('SLTR_MINIMUM_PHP_VERSION', '8.0');
define('SLTR_PLUGIN_FILE', __FILE__);
define('SLTR_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SLTR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SLTR_PLUGIN_URL', plugin_dir_url(__FILE__));


/**
 * Return the official Slotera update namespace when release builds are bound
 * to the future first-party update service. RC builds intentionally leave it
 * empty until an official HTTPS endpoint is available.
 */
function sltr_update_uri(): string
{
    $uri = (string) SLTR_UPDATE_URI;
    $filtered = apply_filters('sltr_update_uri', $uri);
    return is_string($filtered) ? trim($filtered) : $uri;
}

require_once SLTR_PLUGIN_DIR . 'includes/build.php';
require_once SLTR_PLUGIN_DIR . 'includes/autoload.php';
require_once SLTR_PLUGIN_DIR . 'includes/helpers.php';

function sltr_load_textdomain(): void
{
    load_plugin_textdomain(
        'slotera-booking',
        false,
        dirname(SLTR_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('plugins_loaded', 'sltr_load_textdomain');

register_activation_hook(SLTR_PLUGIN_FILE, ['Slotera\\Core\\Activator', 'activate']);
register_deactivation_hook(SLTR_PLUGIN_FILE, ['Slotera\\Core\\Deactivator', 'deactivate']);

function sltr_boot_plugin(): void
{
    if (class_exists('Slotera\\Core\\EnvironmentCheck')) {
        add_action('admin_notices', ['Slotera\\Core\\EnvironmentCheck', 'admin_notice']);
        if (Slotera\Core\EnvironmentCheck::blocking_errors() !== []) {
            return;
        }
    }
    (new Slotera\Core\Plugin())->run();
}

sltr_boot_plugin();
