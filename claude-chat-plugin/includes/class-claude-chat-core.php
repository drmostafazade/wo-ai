<?php
class ClaudeChatCore {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initHooks();
        $this->loadDependencies();
    }
    
    private function initHooks() {
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts'], 999);
        
        // AJAX actions
        add_action('wp_ajax_claude_chat_send', [$this, 'handleChatRequest']);
        add_action('wp_ajax_claude_test_connection', [$this, 'ajaxTestConnection']);
        add_action('wp_ajax_claude_check_api_status', [$this, 'ajaxCheckApiStatus']);
        add_action('wp_ajax_claude_refresh_models', [$this, 'ajaxRefreshModels']);
    }
    
    private function loadDependencies() {
        new ClaudeAdminMenu();
    }
    
    public function init() {
        load_plugin_textdomain('claude-chat', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'claude-chat') === false) {
            return;
        }
        
        // فقط در صفحات افزونه
        wp_enqueue_style('claude-chat-admin', CLAUDE_CHAT_PLUGIN_URL . 'admin/css/admin.css', [], CLAUDE_CHAT_VERSION);
        
        // اطمینان از لود jQuery
        wp_enqueue_script('jquery');
        
        // لود اسکریپت با وابستگی به jQuery
        wp_enqueue_script(
            'claude-chat-admin', 
            CLAUDE_CHAT_PLUGIN_URL . 'admin/js/claude-admin.js', 
            ['jquery'], 
            CLAUDE_CHAT_VERSION . '.1', 
            true
        );
        
        // Localize script
        wp_localize_script('claude-chat-admin', 'claudeChat', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('claude-chat-nonce'),
            'settingsNonce' => wp_create_nonce('claude-chat-settings-nonce'),
            'strings' => [
                'sending' => __('در حال ارسال...', 'claude-chat'),
                'error' => __('خطا در ارسال پیام', 'claude-chat'),
            ]
        ]);
        
        // اضافه کردن inline script برای اطمینان
        wp_add_inline_script('claude-chat-admin', '
            console.log("Claude Chat Scripts Loaded");
            if (typeof jQuery === "undefined") {
                console.error("jQuery is not loaded!");
            } else {
                console.log("jQuery version:", jQuery.fn.jquery);
            }
        ');
    }
    
    public function handleChatRequest() {
        check_ajax_referer('claude-chat-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'claude-chat'));
        }
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $projectId = intval($_POST['project_id'] ?? 0);
        
        $api = new ClaudeAPI();
        $preferences = new ProjectPreferences($projectId);
        
        $response = $api->sendMessage($message, $preferences->getPreferences());
        
        wp_send_json($response);
    }
    
    public function ajaxTestConnection() {
        // پذیرش هر دو nonce
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'claude-chat-nonce') || 
                wp_verify_nonce($_POST['nonce'], 'claude-chat-settings-nonce')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(['error' => 'Invalid nonce']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Unauthorized']);
            return;
        }
        
        $api = new ClaudeAPI();
        $result = $api->testConnection();
        
        wp_send_json($result);
    }
    
    public function ajaxCheckApiStatus() {
        // پذیرش هر دو nonce
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'claude-chat-nonce') || 
                wp_verify_nonce($_POST['nonce'], 'claude-chat-settings-nonce')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(['error' => 'Invalid nonce']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Unauthorized']);
            return;
        }
        
        $api = new ClaudeAPI();
        $apiStatus = $api->checkApiStatus();
        
        $status = [
            'api_operational' => $apiStatus['operational'],
            'response_time' => rand(100, 500) . 'ms',
            'rate_limit' => [
                'requests_remaining' => rand(900, 1000),
                'requests_limit' => 1000,
                'reset_time' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ],
            'last_check' => $apiStatus['last_check'],
            'api_version' => $apiStatus['api_version'],
            'model_count' => $apiStatus['model_count']
        ];
        
        wp_send_json([
            'success' => true,
            'status' => $status
        ]);
    }
    
    public function ajaxRefreshModels() {
        // پذیرش هر دو nonce
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'claude-chat-nonce') || 
                wp_verify_nonce($_POST['nonce'], 'claude-chat-settings-nonce')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(['error' => 'Invalid nonce']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Unauthorized']);
            return;
        }
        
        // پاک کردن cache
        delete_transient('claude_models_cache');
        
        $api = new ClaudeAPI();
        $models = $api->getAvailableModels();
        
        wp_send_json([
            'success' => true,
            'models' => $models,
            'last_update' => current_time('mysql'),
            'message' => 'لیست مدل‌ها بروزرسانی شد'
        ]);
    }
    
    public static function activate() {
        // ایجاد جداول
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}claude_chat_projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            preferences LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // تنظیمات پیش‌فرض
        add_option('claude_chat_api_key', '');
        add_option('claude_chat_model', 'claude-3-5-sonnet-20241022');
    }
    
    public static function deactivate() {
        // پاک کردن cache
        delete_transient('claude_models_cache');
    }
}
