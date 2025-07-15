<?php
class ClaudeAdminMenu {
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }
    
    public function addAdminMenu() {
        add_menu_page(
            __('Claude Chat', 'claude-chat'),
            __('Claude Chat', 'claude-chat'),
            'manage_options',
            'claude-chat',
            [$this, 'renderChatPage'],
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'claude-chat',
            __('تنظیمات', 'claude-chat'),
            __('تنظیمات', 'claude-chat'),
            'manage_options',
            'claude-chat-settings',
            [$this, 'renderSettingsPage']
        );
        
        add_submenu_page(
            'claude-chat',
            __('پروژه‌ها', 'claude-chat'),
            __('پروژه‌ها', 'claude-chat'),
            'manage_options',
            'claude-chat-projects',
            [$this, 'renderProjectsPage']
        );
        
        add_submenu_page(
            'claude-chat',
            __('پرامپت‌ها', 'claude-chat'),
            __('پرامپت‌ها', 'claude-chat'),
            'manage_options',
            'claude-chat-prompts',
            [$this, 'renderPromptsPage']
        );
    }
    
    public function renderChatPage() {
        include CLAUDE_CHAT_PLUGIN_DIR . 'admin/views/chat-page.php';
    }
    
    public function renderSettingsPage() {
        include CLAUDE_CHAT_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    public function renderProjectsPage() {
        include CLAUDE_CHAT_PLUGIN_DIR . 'admin/views/projects-page.php';
    }
    
    public function renderPromptsPage() {
        include CLAUDE_CHAT_PLUGIN_DIR . 'admin/views/prompts-page.php';
    }
}
