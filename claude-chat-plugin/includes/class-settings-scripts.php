<?php
class ClaudeSettingsScripts {
    public static function enqueueScripts($hook) {
        // فقط در صفحات مربوط به افزونه
        if (strpos($hook, 'claude-chat') === false) {
            return;
        }
        
        // اضافه کردن nonce برای همه صفحات
        wp_localize_script('claude-chat-admin', 'claudeChatSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('claude-chat-settings-nonce')
        ]);
    }
}
