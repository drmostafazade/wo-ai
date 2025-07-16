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

global $wpdb;
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>Database Check</title>
    <style>
        body { font-family: Tahoma, Arial; direction: rtl; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background: #f0f0f0; }
        .success { color: green; }
        .error { color: red; }
        .button { padding: 10px 20px; background: #007cba; color: white; text-decoration: none; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>بررسی وضعیت دیتابیس AI Dr. Mostafazade</h1>
    
    <h2>جداول قدیمی CPH:</h2>
    <table>
        <tr><th>نام جدول</th><th>وضعیت</th><th>تعداد رکورد</th></tr>
        <?php
        $old_tables = ['cph_memory', 'cph_embeddings', 'cph_clusters', 'cph_contexts', 'cph_feedback', 'cph_arvan_sync'];
        $old_data_exists = false;
        
        foreach ($old_tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'");
            $count = 0;
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table}");
                if ($count > 0) $old_data_exists = true;
            }
            
            echo "<tr>";
            echo "<td>{$full_table}</td>";
            echo "<td class='" . ($exists ? "success" : "error") . "'>" . ($exists ? "✅ موجود" : "❌ یافت نشد") . "</td>";
            echo "<td>{$count}</td>";
            echo "</tr>";
        }
        ?>
    </table>
    
    <h2>جداول جدید ADM:</h2>
    <table>
        <tr><th>نام جدول</th><th>وضعیت</th><th>تعداد رکورد</th></tr>
        <?php
        $new_tables = ['adm_memory', 'adm_embeddings', 'adm_clusters', 'adm_contexts', 'adm_feedback', 'adm_arvan_sync'];
        
        foreach ($new_tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'");
            $count = 0;
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table}");
            }
            
            echo "<tr>";
            echo "<td>{$full_table}</td>";
            echo "<td class='" . ($exists ? "success" : "error") . "'>" . ($exists ? "✅ موجود" : "❌ یافت نشد") . "</td>";
            echo "<td>{$count}</td>";
            echo "</tr>";
        }
        ?>
    </table>
    
    <?php if ($old_data_exists): ?>
        <h2>انتقال داده‌ها</h2>
        <p>داده‌های قدیمی یافت شد. برای انتقال روی دکمه زیر کلیک کنید:</p>
        <a href="?action=migrate" class="button">انتقال داده‌ها از CPH به ADM</a>
        
        <?php
        if (isset($_GET['action']) && $_GET['action'] === 'migrate') {
            echo "<h3>در حال انتقال...</h3>";
            
            // Migrate memories
            $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}adm_memory (id, user_query, ai_response, created_at) 
                         SELECT id, user_query, ai_response, created_at FROM {$wpdb->prefix}cph_memory");
            echo "<p>✅ انتقال " . $wpdb->rows_affected . " خاطره</p>";
            
            // Migrate other tables if they exist
            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}cph_embeddings'")) {
                $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}adm_embeddings 
                             SELECT * FROM {$wpdb->prefix}cph_embeddings");
                echo "<p>✅ انتقال " . $wpdb->rows_affected . " embedding</p>";
            }
            
            echo '<p><strong>انتقال کامل شد!</strong></p>';
            echo '<script>setTimeout(() => location.href = "?", 2000);</script>';
        }
        ?>
    <?php endif; ?>
    
    <h2>لینک‌های مفید</h2>
    <a href="<?php echo admin_url('admin.php?page=ai-dr-mostafazade'); ?>" class="button">تنظیمات افزونه</a>
    <a href="generate-embeddings.php" class="button">ایجاد Embeddings</a>
    <a href="create-chat-page.php" class="button">ایجاد صفحه چت</a>
</body>
</html>
