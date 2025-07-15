<?php
/**
 * Plugin Name: Claude Chat Plugin
 * Plugin URI: https://bsepar.com
 * Description: افزونه چت با Claude API برای کدنویسی و توسعه
 * Version: 1.0.0
 * Author: Dr. Mostafazade
 * Author URI: https://bsepar.com
 * License: GPL v2 or later
 * Text Domain: claude-chat
 * Domain Path: /languages
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ثابت‌های افزونه
define('CLAUDE_CHAT_VERSION', '1.0.0');
define('CLAUDE_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLAUDE_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLAUDE_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// بارگذاری کلاس‌های اصلی
require_once CLAUDE_CHAT_PLUGIN_DIR . 'includes/class-claude-chat-core.php';
require_once CLAUDE_CHAT_PLUGIN_DIR . 'includes/class-claude-api.php';
require_once CLAUDE_CHAT_PLUGIN_DIR . 'includes/class-project-preferences.php';
require_once CLAUDE_CHAT_PLUGIN_DIR . 'includes/class-admin-menu.php';

// فعال‌سازی افزونه
register_activation_hook(__FILE__, ['ClaudeChatCore', 'activate']);
register_deactivation_hook(__FILE__, ['ClaudeChatCore', 'deactivate']);

// شروع افزونه
add_action('plugins_loaded', function() {
    ClaudeChatCore::getInstance();
});
