<?php
// Find wp-load.php
$wp_load_paths = [
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../../../wp-load.php',
    dirname(__FILE__) . '/../../../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) die('Could not find wp-load.php');
if (!current_user_can('manage_options')) die('Please login as administrator');

// Copy template to theme directory
$template_source = dirname(__FILE__) . '/chat-fullwidth-template.php';
$template_dest = get_template_directory() . '/template-ai-chat-fullwidth.php';

if (!file_exists($template_dest)) {
    copy($template_source, $template_dest);
}

// Create or update page
$page = get_page_by_path('ai-chat-fullwidth');

if (!$page) {
    $page_id = wp_insert_post([
        'post_title' => 'چت AI تمام صفحه',
        'post_name' => 'ai-chat-fullwidth',
        'post_content' => '[ai_dr_chat height="100vh" theme="dark" tabs="true" context="true"]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'page_template' => 'template-ai-chat-fullwidth.php'
    ]);
} else {
    $page_id = $page->ID;
    update_post_meta($page_id, '_wp_page_template', 'template-ai-chat-fullwidth.php');
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>Create Full Width Page</title>
    <style>
        body { font-family: Tahoma, Arial; direction: rtl; padding: 20px; }
        .success { color: green; }
        .button { padding: 10px 20px; background: #007cba; color: white; text-decoration: none; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>ایجاد صفحه چت تمام صفحه</h1>
    
    <div class="success">✅ صفحه چت تمام صفحه ایجاد شد!</div>
    
    <a href="<?php echo get_permalink($page_id); ?>" class="button" target="_blank">مشاهده چت تمام صفحه</a>
    <a href="<?php echo get_edit_post_link($page_id); ?>" class="button">ویرایش صفحه</a>
    
    <h2>روش‌های جایگزین:</h2>
    <p>1. از URL پارامتر استفاده کنید:</p>
    <code><?php echo home_url('/ai-chat-fullwidth/?fullscreen=1'); ?></code>
    
    <p>2. کد CSS زیر را به تم اضافه کنید:</p>
    <pre>
.page-template-template-ai-chat-fullwidth header,
.page-template-template-ai-chat-fullwidth footer,
.page-template-template-ai-chat-fullwidth .sidebar {
    display: none !important;
}
    </pre>
</body>
</html>
