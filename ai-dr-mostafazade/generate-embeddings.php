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

// Load required classes
require_once(dirname(__FILE__) . '/inc/class-adm-vector-db.php');

global $wpdb;
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>Generate Embeddings</title>
    <style>
        body { font-family: Tahoma, Arial; direction: rtl; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #e7f3ff; padding: 10px; margin: 10px 0; }
        .button { padding: 10px 20px; background: #007cba; color: white; text-decoration: none; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>ایجاد Embeddings برای خاطرات</h1>
    
    <?php
    $options = get_option('adm_options', []);
    
    if (empty($options['openai_api_key'])) {
        echo '<div class="error">❌ کلید OpenAI API تنظیم نشده است. لطفاً ابتدا در تنظیمات افزونه کلید API را وارد کنید.</div>';
        echo '<a href="' . admin_url('admin.php?page=ai-dr-mostafazade') . '" class="button">رفتن به تنظیمات</a>';
        die();
    }
    
    // Check current status
    $total_memories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_memory");
    $total_embeddings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings");
    
    echo '<div class="info">';
    echo "<p>تعداد کل خاطرات: <strong>{$total_memories}</strong></p>";
    echo "<p>تعداد Embeddings موجود: <strong>{$total_embeddings}</strong></p>";
    echo "<p>نیاز به ایجاد: <strong>" . ($total_memories - $total_embeddings) . "</strong></p>";
    echo '</div>';
    
    if (!isset($_GET['start'])) {
        ?>
        <p>برای ایجاد embeddings روی دکمه زیر کلیک کنید:</p>
        <a href="?start=1" class="button">شروع ایجاد Embeddings</a>
        <?php
    } else {
        // Get memories without embeddings
        $memories = $wpdb->get_results(
            "SELECT m.* FROM {$wpdb->prefix}adm_memory m
             LEFT JOIN {$wpdb->prefix}adm_embeddings e ON m.id = e.memory_id
             WHERE e.id IS NULL
             LIMIT 10"
        );
        
        if (empty($memories)) {
            echo '<div class="success">✅ همه خاطرات دارای embedding هستند!</div>';
            
            if ($total_embeddings >= 10) {
                echo '<p>حالا می‌توانید Clusters را بروزرسانی کنید:</p>';
                echo '<a href="' . admin_url('admin.php?page=ai-dr-mostafazade') . '" class="button">رفتن به تنظیمات</a>';
            }
        } else {
            $vector_db = new ADM_Vector_DB();
            $generated = 0;
            
            echo '<h3>در حال پردازش...</h3>';
            echo '<ul>';
            
            foreach ($memories as $memory) {
                $text = $memory->user_query . ' ' . substr($memory->ai_response, 0, 500);
                
                try {
                    $embedding = $vector_db->generate_embedding($text);
                    
                    if ($embedding && is_array($embedding)) {
                        $vector_db->save_embedding($memory->id, $embedding);
                        $generated++;
                        echo '<li class="success">✅ Embedding ایجاد شد برای ID: ' . $memory->id . '</li>';
                    } else {
                        echo '<li class="error">❌ خطا در ایجاد embedding برای ID: ' . $memory->id . '</li>';
                    }
                } catch (Exception $e) {
                    echo '<li class="error">❌ خطا: ' . $e->getMessage() . '</li>';
                }
                
                flush();
                sleep(1); // Rate limiting
            }
            
            echo '</ul>';
            echo "<p><strong>تعداد {$generated} embedding ایجاد شد</strong></p>";
            
            // Check if more to process
            $remaining = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}adm_memory m
                 LEFT JOIN {$wpdb->prefix}adm_embeddings e ON m.id = e.memory_id
                 WHERE e.id IS NULL"
            );
            
            if ($remaining > 0) {
                echo "<p>باقیمانده: {$remaining}</p>";
                echo '<a href="?start=1" class="button">ادامه پردازش</a>';
            }
        }
    }
    ?>
</body>
</html>
