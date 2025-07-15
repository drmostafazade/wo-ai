<?php
if (isset($_POST['submit'])) {
    check_admin_referer('claude-chat-settings');
    
    update_option('claude_chat_api_key', sanitize_text_field($_POST['api_key']));
    update_option('claude_chat_model', sanitize_text_field($_POST['model']));
    
    echo '<div class="notice notice-success"><p>' . __('تنظیمات ذخیره شد.', 'claude-chat') . '</p></div>';
}

$apiKey = get_option('claude_chat_api_key', '');
$selectedModel = get_option('claude_chat_model', 'claude-3-opus-20240229');

// دریافت لیست مدل‌ها
$api = new ClaudeAPI();
$models = $api->getAvailableModels();
?>

<div class="wrap">
    <h1><?php _e('تنظیمات Claude Chat', 'claude-chat'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('claude-chat-settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_key"><?php _e('Claude API Key', 'claude-chat'); ?></label>
                </th>
                <td>
                    <input type="password" id="api_key" name="api_key" 
                           value="<?php echo esc_attr($apiKey); ?>" class="regular-text" />
                    <p class="description">
                        <?php _e('کلید API خود را از', 'claude-chat'); ?> 
                        <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a> 
                        <?php _e('دریافت کنید.', 'claude-chat'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="model"><?php _e('مدل Claude', 'claude-chat'); ?></label>
                </th>
                <td>
                    <div class="claude-models-list">
                        <?php foreach ($models as $model): ?>
                            <div class="model-option">
                                <label>
                                    <input type="radio" name="model" value="<?php echo esc_attr($model['id']); ?>" 
                                           <?php checked($selectedModel, $model['id']); ?> />
                                    <strong><?php echo esc_html($model['name']); ?></strong>
                                    <span class="model-id">(<?php echo esc_html($model['id']); ?>)</span>
                                </label>
                                <div class="model-details">
                                    <p class="description"><?php echo esc_html($model['description']); ?></p>
                                    <div class="model-specs">
                                        <span class="spec">
                                            <strong>Context:</strong> <?php echo number_format($model['context_window']); ?> tokens
                                        </span>
                                        <span class="spec">
                                            <strong>Max Output:</strong> <?php echo number_format($model['max_output']); ?> tokens
                                        </span>
                                        <span class="spec">
                                            <strong>Training:</strong> <?php echo esc_html($model['training_cutoff']); ?>
                                        </span>
                                    </div>
                                    <div class="model-capabilities">
                                        <strong>قابلیت‌ها:</strong>
                                        <?php foreach ($model['capabilities'] as $capability): ?>
                                            <span class="capability-tag"><?php echo esc_html($capability); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="model-refresh">
                        <button type="button" id="refresh-models" class="button button-secondary">
                            <?php _e('بروزرسانی لیست مدل‌ها', 'claude-chat'); ?>
                        </button>
                        <span class="spinner"></span>
                    </div>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="claude-test-connection">
        <h2><?php _e('تست اتصال', 'claude-chat'); ?></h2>
        <button id="test-connection" class="button button-secondary">
            <?php _e('تست API', 'claude-chat'); ?>
        </button>
        <div id="test-result"></div>
    </div>
    
    <div class="claude-api-status">
        <h2><?php _e('وضعیت API', 'claude-chat'); ?></h2>
        <div id="api-status-info">
            <p>برای مشاهده وضعیت API کلیک کنید.</p>
        </div>
        <button id="check-api-status" class="button button-secondary">
            <?php _e('بررسی وضعیت', 'claude-chat'); ?>
        </button>
    </div>
</div>

<style>
.claude-models-list {
    max-width: 800px;
}

.model-option {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    background: #f9f9f9;
    transition: all 0.3s ease;
}

.model-option:hover {
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.model-option input[type="radio"] {
    margin-right: 5px;
}

.model-id {
    color: #666;
    font-size: 12px;
    font-family: monospace;
}

.model-details {
    margin-left: 25px;
    margin-top: 10px;
}

.model-specs {
    display: flex;
    gap: 20px;
    margin: 10px 0;
    font-size: 13px;
}

.model-specs .spec {
    background: #e0e0e0;
    padding: 3px 8px;
    border-radius: 3px;
}

.model-capabilities {
    margin-top: 10px;
}

.capability-tag {
    display: inline-block;
    background: #0073aa;
    color: white;
    padding: 3px 10px;
    border-radius: 3px;
    margin: 3px;
    font-size: 12px;
}

.model-refresh {
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.claude-api-status {
    margin-top: 40px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 5px;
}

#api-status-info {
    margin: 15px 0;
    padding: 15px;
    background: white;
    border-radius: 3px;
    min-height: 50px;
}
</style>
