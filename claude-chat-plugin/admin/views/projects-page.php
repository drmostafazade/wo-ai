<?php
$preferences = new ProjectPreferences();

// ذخیره پروژه جدید
if (isset($_POST['create_project'])) {
    check_admin_referer('claude-chat-project');
    
    $name = sanitize_text_field($_POST['project_name']);
    $prefs = [
        'language' => sanitize_text_field($_POST['language'] ?? 'Persian'),
        'tech_stack' => array_map('sanitize_text_field', $_POST['tech_stack'] ?? []),
        'project_context' => sanitize_textarea_field($_POST['project_context'] ?? ''),
        'response_format' => sanitize_text_field($_POST['response_format'] ?? 'script')
    ];
    
    $preferences->createProject($name, $prefs);
    echo '<div class="notice notice-success"><p>' . __('پروژه ایجاد شد.', 'claude-chat') . '</p></div>';
}

$projects = $preferences->getAllProjects();
?>

<div class="wrap">
    <h1><?php _e('مدیریت پروژه‌ها', 'claude-chat'); ?></h1>
    
    <div class="claude-projects-grid">
        <div class="add-new-project">
            <h2><?php _e('پروژه جدید', 'claude-chat'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('claude-chat-project'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="project_name"><?php _e('نام پروژه', 'claude-chat'); ?></label></th>
                        <td><input type="text" id="project_name" name="project_name" class="regular-text" required /></td>
                    </tr>
                    
                    <tr>
                        <th><label for="language"><?php _e('زبان پاسخ', 'claude-chat'); ?></label></th>
                        <td>
                            <select id="language" name="language">
                                <option value="Persian">فارسی</option>
                                <option value="English">English</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label><?php _e('استک فنی', 'claude-chat'); ?></label></th>
                        <td>
                            <label><input type="checkbox" name="tech_stack[]" value="PHP"> PHP</label><br>
                            <label><input type="checkbox" name="tech_stack[]" value="WordPress"> WordPress</label><br>
                            <label><input type="checkbox" name="tech_stack[]" value="Laravel"> Laravel</label><br>
                            <label><input type="checkbox" name="tech_stack[]" value="JavaScript"> JavaScript</label><br>
                            <label><input type="checkbox" name="tech_stack[]" value="Vue.js"> Vue.js</label><br>
                            <label><input type="checkbox" name="tech_stack[]" value="React"> React</label><br>
                            <label><input type="checkbox" name="tech_stack[]" value="Python"> Python</label><br>
                            <label><input type="checkbox" name="tech_stack[]" value="MySQL"> MySQL</label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="response_format"><?php _e('فرمت پاسخ', 'claude-chat'); ?></label></th>
                        <td>
                            <select id="response_format" name="response_format">
                                <option value="script">اسکریپت قابل اجرا</option>
                                <option value="detailed">توضیحات + کد</option>
                                <option value="code_only">فقط کد</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="project_context"><?php _e('توضیحات پروژه', 'claude-chat'); ?></label></th>
                        <td>
                            <textarea id="project_context" name="project_context" rows="5" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="create_project" class="button button-primary" 
                           value="<?php _e('ایجاد پروژه', 'claude-chat'); ?>" />
                </p>
            </form>
        </div>
        
        <div class="existing-projects">
            <h2><?php _e('پروژه‌های موجود', 'claude-chat'); ?></h2>
            <?php if ($projects): ?>
                <div class="projects-list">
                    <?php foreach ($projects as $project): 
                        $prefs = json_decode($project->preferences, true);
                    ?>
                        <div class="project-card">
                            <h3><?php echo esc_html($project->name); ?></h3>
                            <p><strong>زبان:</strong> <?php echo esc_html($prefs['language'] ?? 'Persian'); ?></p>
                            <p><strong>استک:</strong> <?php echo esc_html(implode(', ', $prefs['tech_stack'] ?? [])); ?></p>
                            <p><strong>ایجاد:</strong> <?php echo esc_html($project->created_at); ?></p>
                            <div class="project-actions">
                                <a href="#" class="button button-small edit-project" data-id="<?php echo esc_attr($project->id); ?>">
                                    <?php _e('ویرایش', 'claude-chat'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('هنوز پروژه‌ای ایجاد نشده است.', 'claude-chat'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
