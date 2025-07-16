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

if (!$wp_loaded) {
    die('Could not find wp-load.php');
}

if (!current_user_can('manage_options')) {
    die('Please login as administrator');
}

// Activate terminal addon
$active_addons = get_option('adm_active_addons', []);
if (!in_array('terminal', $active_addons)) {
    $active_addons[] = 'terminal';
    update_option('adm_active_addons', $active_addons);
}

// Ensure all tables exist
global $wpdb;
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once(dirname(__FILE__) . '/inc/class-adm-db-migration.php');

ADM_DB_Migration::run();

?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>Apply All Fixes</title>
    <style>
        body { font-family: Tahoma, Arial; direction: rtl; padding: 20px; }
        .success { color: green; }
        .button { padding: 10px 20px; background: #007cba; color: white; text-decoration: none; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>اعمال تمام اصلاحات</h1>
    
    <p class="success">✅ Terminal addon فعال شد</p>
    <p class="success">✅ جداول دیتابیس بررسی شدند</p>
    
    <h2>مراحل بعدی:</h2>
    
    <a href="check-tables.php" class="button">1. بررسی و انتقال داده‌ها</a>
    <a href="<?php echo admin_url('admin.php?page=ai-dr-mostafazade'); ?>" class="button">2. تنظیم API Keys</a>
    <a href="generate-embeddings.php" class="button">3. ایجاد Embeddings</a>
    <a href="create-chat-page.php" class="button">4. ایجاد صفحه چت</a>
    
    <h2>شورت‌کد:</h2>
    <p style="background: #f0f0f1; padding: 15px; direction: ltr;">
        [ai_dr_chat]
    </p>
</body>
</html>
