<?php
// ذخیره پرامپت جدید
if (isset($_POST['save_prompt'])) {
    check_admin_referer('claude-chat-prompts');
    
    $prompts = get_option('claude_chat_prompts', []);
    $new_prompt = [
        'id' => uniqid(),
        'name' => sanitize_text_field($_POST['prompt_name']),
        'category' => sanitize_text_field($_POST['prompt_category']),
        'content' => sanitize_textarea_field($_POST['prompt_content']),
        'variables' => array_map('sanitize_text_field', explode(',', $_POST['prompt_variables'] ?? '')),
        'created_at' => current_time('mysql')
    ];
    
    $prompts[] = $new_prompt;
    update_option('claude_chat_prompts', $prompts);
    
    echo '<div class="notice notice-success"><p>پرامپت ذخیره شد.</p></div>';
}

// حذف پرامپت
if (isset($_GET['delete_prompt']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_prompt_' . $_GET['delete_prompt'])) {
        $prompts = get_option('claude_chat_prompts', []);
        $prompts = array_filter($prompts, function($p) {
            return $p['id'] !== $_GET['delete_prompt'];
        });
        update_option('claude_chat_prompts', array_values($prompts));
        echo '<div class="notice notice-success"><p>پرامپت حذف شد.</p></div>';
    }
}

$prompts = get_option('claude_chat_prompts', []);
$categories = ['کدنویسی', 'دیباگ', 'بهینه‌سازی', 'مستندسازی', 'تحلیل', 'سایر'];
?>

<div class="wrap">
    <h1><?php _e('مدیریت پرامپت‌ها', 'claude-chat'); ?></h1>
    
    <div class="claude-prompts-container">
        <div class="add-prompt-section">
            <h2>پرامپت جدید</h2>
            <form method="post" action="">
                <?php wp_nonce_field('claude-chat-prompts'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="prompt_name">نام پرامپت</label></th>
                        <td><input type="text" id="prompt_name" name="prompt_name" class="regular-text" required /></td>
                    </tr>
                    
                    <tr>
                        <th><label for="prompt_category">دسته‌بندی</label></th>
                        <td>
                            <select id="prompt_category" name="prompt_category" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="prompt_content">محتوای پرامپت</label></th>
                        <td>
                            <textarea id="prompt_content" name="prompt_content" rows="10" class="large-text code" required 
                                placeholder="محتوای پرامپت خود را بنویسید. از {{variable}} برای متغیرها استفاده کنید."></textarea>
                            <p class="description">
                                می‌توانید از متغیرها استفاده کنید: {{project_name}}, {{tech_stack}}, {{language}}
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="prompt_variables">متغیرهای سفارشی</label></th>
                        <td>
                            <input type="text" id="prompt_variables" name="prompt_variables" class="regular-text" 
                                   placeholder="متغیر1, متغیر2, متغیر3" />
                            <p class="description">متغیرهای اضافی را با کاما جدا کنید</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_prompt" class="button button-primary" value="ذخیره پرامپت" />
                </p>
            </form>
        </div>
        
        <div class="prompts-list-section">
            <h2>پرامپت‌های ذخیره شده</h2>
            
            <?php if (empty($prompts)): ?>
                <p>هنوز پرامپتی ذخیره نشده است.</p>
            <?php else: ?>
                <div class="prompts-grid">
                    <?php 
                    // گروه‌بندی بر اساس دسته
                    $grouped = [];
                    foreach ($prompts as $prompt) {
                        $cat = $prompt['category'] ?? 'سایر';
                        $grouped[$cat][] = $prompt;
                    }
                    
                    foreach ($grouped as $category => $categoryPrompts): ?>
                        <div class="prompt-category">
                            <h3><?php echo esc_html($category); ?></h3>
                            <?php foreach ($categoryPrompts as $prompt): ?>
                                <div class="prompt-card">
                                    <h4><?php echo esc_html($prompt['name']); ?></h4>
                                    <div class="prompt-preview">
                                        <?php echo nl2br(esc_html(substr($prompt['content'], 0, 150))); ?>...
                                    </div>
                                    <div class="prompt-actions">
                                        <button class="button button-small use-prompt" 
                                                data-prompt="<?php echo esc_attr(json_encode($prompt)); ?>">
                                            استفاده
                                        </button>
                                        <button class="button button-small copy-prompt" 
                                                data-content="<?php echo esc_attr($prompt['content']); ?>">
                                            کپی
                                        </button>
                                        <a href="<?php echo wp_nonce_url(add_query_arg('delete_prompt', $prompt['id']), 'delete_prompt_' . $prompt['id']); ?>" 
                                           class="button button-small button-link-delete"
                                           onclick="return confirm('آیا مطمئن هستید؟');">
                                            حذف
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="default-prompts">
        <h2>پرامپت‌های پیش‌فرض</h2>
        <div class="default-prompts-grid">
            <div class="default-prompt-card">
                <h4>🚀 شروع پروژه جدید</h4>
                <p>من می‌خواهم یک {{project_type}} با استفاده از {{tech_stack}} بسازم. لطفاً ساختار پایه پروژه را ایجاد کن.</p>
            </div>
            
            <div class="default-prompt-card">
                <h4>🐛 دیباگ کد</h4>
                <p>کد زیر خطا دارد. لطفاً آن را بررسی و مشکل را رفع کن:\n\n{{code}}</p>
            </div>
            
            <div class="default-prompt-card">
                <h4>⚡ بهینه‌سازی کد</h4>
                <p>کد زیر را از نظر عملکرد و خوانایی بهینه کن:\n\n{{code}}</p>
            </div>
            
            <div class="default-prompt-card">
                <h4>📝 مستندسازی</h4>
                <p>برای کد زیر مستندات کامل به زبان {{language}} بنویس:\n\n{{code}}</p>
            </div>
            
            <div class="default-prompt-card">
                <h4>🔍 تحلیل کد</h4>
                <p>کد زیر را تحلیل کن و نقاط قوت و ضعف آن را بیان کن:\n\n{{code}}</p>
            </div>
            
            <div class="default-prompt-card">
                <h4>🏗️ معماری سیستم</h4>
                <p>برای پروژه {{project_name}} که قرار است {{requirements}} را انجام دهد، معماری مناسب پیشنهاد بده.</p>
            </div>
        </div>
    </div>
</div>

<style>
.claude-prompts-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    margin-top: 20px;
}

.prompts-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.prompt-category h3 {
    background: #f0f0f1;
    padding: 10px;
    margin: 0 0 10px 0;
    border-radius: 5px;
}

.prompt-card {
    background: white;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 10px;
}

.prompt-preview {
    color: #666;
    font-size: 13px;
    margin: 10px 0;
    font-family: monospace;
    background: #f9f9f9;
    padding: 10px;
    border-radius: 3px;
}

.prompt-actions {
    display: flex;
    gap: 5px;
    margin-top: 10px;
}

.default-prompts {
    margin-top: 40px;
}

.default-prompts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.default-prompt-card {
    background: #f0f8ff;
    border: 1px solid #b0d4ff;
    padding: 15px;
    border-radius: 5px;
}

.default-prompt-card h4 {
    margin-top: 0;
}

.default-prompt-card p {
    font-family: monospace;
    font-size: 12px;
    background: white;
    padding: 10px;
    border-radius: 3px;
    white-space: pre-wrap;
}
</style>
