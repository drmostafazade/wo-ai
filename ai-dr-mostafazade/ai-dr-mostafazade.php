<?php
/**
* Plugin Name:       AI Dr. Mostafazade - Claude Pro Hub
* Description:       نسخه پیشرفته هاب تخصصی کلاد با حافظه معنایی Vector Database و موتور شخصیت‌سازی داینامیک
* Version:           9.0.0
* Author:            Dr. Mostafazade AI Assistant
* Text Domain:       ai-dr-mostafazade
*/

if (!defined('WPINC')) die;

define('ADM_VERSION', '9.0.0');
define('ADM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADM_PLUGIN_FILE', __FILE__);

// Load core classes
require_once ADM_PLUGIN_DIR . 'inc/class-adm-core.php';
require_once ADM_PLUGIN_DIR . 'inc/class-adm-cron.php';
require_once ADM_PLUGIN_DIR . 'inc/class-adm-rest-api.php';
if (defined('WP_CLI') && WP_CLI) {
   require_once ADM_PLUGIN_DIR . 'inc/class-adm-cli.php';
}
require_once ADM_PLUGIN_DIR . 'inc/class-adm-db-migration.php';

// Activation hook
register_activation_hook(__FILE__, function() {
   ADM_DB_Migration::run();
   ADM_Core::activate();
});

// Initialize plugin
add_action('plugins_loaded', function() {
   new ADM_Core();
});

// Load text domain
add_action('init', function() {
   load_plugin_textdomain('ai-dr-mostafazade', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
