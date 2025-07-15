<div class="wrap claude-chat-wrap">
    <h1><?php _e('Claude Chat - محیط توسعه', 'claude-chat'); ?></h1>
    
    <div class="claude-chat-container">
        <div class="chat-sidebar">
            <h3><?php _e('پروژه فعال', 'claude-chat'); ?></h3>
            <select id="active-project" class="widefat">
                <option value="0"><?php _e('پروژه پیش‌فرض', 'claude-chat'); ?></option>
                <?php
                $preferences = new ProjectPreferences();
                $projects = $preferences->getAllProjects();
                foreach ($projects as $project) {
                    echo '<option value="' . esc_attr($project->id) . '">' . esc_html($project->name) . '</option>';
                }
                ?>
            </select>
            
            <div class="project-preferences" id="project-preferences">
                <!-- تنظیمات پروژه با JavaScript لود می‌شود -->
            </div>
        </div>
        
        <div class="chat-main">
            <div class="chat-messages" id="chat-messages">
                <div class="chat-message system">
                    <p><?php _e('سلام! من Claude هستم. آماده کمک به شما در کدنویسی و توسعه پروژه.', 'claude-chat'); ?></p>
                </div>
            </div>
            
            <div class="chat-input-area">
                <textarea id="chat-input" class="widefat" rows="5" 
                    placeholder="<?php _e('پیام خود را اینجا بنویسید... (Shift+Enter برای خط جدید)', 'claude-chat'); ?>"></textarea>
                <div class="chat-actions">
                    <button id="send-message" class="button button-primary">
                        <?php _e('ارسال', 'claude-chat'); ?>
                    </button>
                    <button id="clear-chat" class="button">
                        <?php _e('پاک کردن چت', 'claude-chat'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
