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
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>Create Chat Page</title>
    <style>
        body { font-family: Tahoma, Arial; direction: rtl; padding: 20px; }
        .success { color: green; }
        .info { background: #e7f3ff; padding: 15px; margin: 10px 0; }
        .button { padding: 10px 20px; background: #007cba; color: white; text-decoration: none; display: inline-block; margin: 10px 5px; }
        pre { background: #f4f4f4; padding: 15px; direction: ltr; text-align: left; }
    </style>
</head>
<body>
    <h1>ایجاد صفحه چت</h1>
    
    <?php
    // Check if page exists
    $page = get_page_by_path('ai-dr-chat');
    
    if (!$page) {
        // Create new page
        $page_id = wp_insert_post([
            'post_title' => 'چت با دکتر مصطفی‌زاده AI',
            'post_name' => 'ai-dr-chat',
            'post_content' => '[ai_dr_chat height="calc(100vh - 100px)" theme="dark" tabs="true" context="true"]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'page_template' => 'full-width.php' // If theme has full-width template
        ]);
        
        if ($page_id) {
            echo '<div class="success">✅ صفحه چت با موفقیت ایجاد شد!</div>';
            echo '<a href="' . get_permalink($page_id) . '" class="button" target="_blank">مشاهده صفحه چت</a>';
            echo '<a href="' . get_edit_post_link($page_id) . '" class="button">ویرایش صفحه</a>';
        }
    } else {
        echo '<div class="info">صفحه چت قبلاً ایجاد شده است.</div>';
        echo '<a href="' . get_permalink($page->ID) . '" class="button" target="_blank">مشاهده صفحه چت</a>';
        echo '<a href="' . get_edit_post_link($page->ID) . '" class="button">ویرایش صفحه</a>';
    }
    ?>
    
    <h2>شورت‌کدهای موجود:</h2>
    <pre>
[ai_dr_chat]                                              // چت پیش‌فرض
[ai_dr_chat height="100vh"]                              // تمام صفحه
[ai_dr_chat height="600px"]                              // ارتفاع مشخص
[ai_dr_chat theme="dark"]                                // تم تیره
[ai_dr_chat theme="light"]                               // تم روشن
[ai_dr_chat tabs="false"]                                // بدون تب
[ai_dr_chat context="false"]                             // بدون آپلود فایل
[ai_dr_chat height="calc(100vh - 100px)" theme="light"] // ترکیبی
    </pre>
    
    <h2>راهنمای استفاده:</h2>
    <ol>
        <li>شورت‌کد را در هر صفحه یا نوشته قرار دهید</li>
        <li>برای تمام صفحه، از قالب "تمام عرض" استفاده کنید</li>
        <li>API keys را در تنظیمات وارد کنید</li>
    </ol>
    
    <a href="<?php echo admin_url('admin.php?page=ai-dr-mostafazade'); ?>" class="button">رفتن به تنظیمات</a>
</body>
</html>
