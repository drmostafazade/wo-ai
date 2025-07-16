<?php
/**
* Quick Installation Test
* Access via: /wp-content/plugins/ai-dr-mostafazade/test-installation.php
*/

// Load WordPress
$wp_load_paths = [
   '../../../../wp-load.php',
   '../../../wp-load.php',
   '../../wp-load.php'
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
   die('Could not load WordPress');
}

if (!current_user_can('manage_options')) {
   die('Unauthorized - Please login as administrator');
}

// Start output
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
   <meta charset="UTF-8">
   <title>AI Dr. Mostafazade - Installation Test</title>
   <style>
       body {
           font-family: Tahoma, Arial, sans-serif;
           background: #f1f1f1;
           margin: 0;
           padding: 20px;
           direction: rtl;
       }
       .container {
           max-width: 800px;
           margin: 0 auto;
           background: #fff;
           padding: 30px;
           border-radius: 8px;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       h1 {
           color: #23282d;
           border-bottom: 2px solid #007cba;
           padding-bottom: 10px;
       }
       h2 {
           color: #007cba;
           margin-top: 30px;
       }
       .status-item {
           padding: 10px;
           margin: 5px 0;
           border-radius: 4px;
           display: flex;
           justify-content: space-between;
           align-items: center;
       }
       .status-ok {
           background: #d4edda;
           color: #155724;
       }
       .status-error {
           background: #f8d7da;
           color: #721c24;
       }
       .status-warning {
           background: #fff3cd;
           color: #856404;
       }
       pre {
           background: #f4f4f4;
           padding: 15px;
           border-radius: 4px;
           overflow-x: auto;
           direction: ltr;
           text-align: left;
       }
       .icon {
           font-size: 20px;
       }
       .back-link {
           display: inline-block;
           margin-top: 20px;
           color: #007cba;
           text-decoration: none;
       }
       .back-link:hover {
           text-decoration: underline;
       }
   </style>
</head>
<body>
<div class="container">
   <h1>🚀 AI Dr. Mostafazade - تست نصب</h1>
   
   <?php
   global $wpdb;
   
   // Plugin activation check
   $plugin_active = is_plugin_active('ai-dr-mostafazade/ai-dr-mostafazade.php');
   ?>
   
   <h2>وضعیت افزونه</h2>
   <div class="status-item <?php echo $plugin_active ? 'status-ok' : 'status-error'; ?>">
       <span>فعال بودن افزونه</span>
       <span class="icon"><?php echo $plugin_active ? '✅' : '❌'; ?></span>
   </div>
   
   <h2>جداول دیتابیس</h2>
   <?php
   $tables = [
       'adm_memory' => 'جدول اصلی حافظه',
       'adm_embeddings' => 'بردارهای معنایی',
       'adm_clusters' => 'خوشه‌های K-means',
       'adm_contexts' => 'زمینه‌های فایل',
       'adm_feedback' => 'بازخورد کاربران',
       'adm_arvan_sync' => 'همگام‌سازی ArvanCloud'
   ];
   
   foreach ($tables as $table => $desc) {
       $full_table = $wpdb->prefix . $table;
       $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") == $full_table;
       $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$full_table}") : 0;
       ?>
       <div class="status-item <?php echo $exists ? 'status-ok' : 'status-error'; ?>">
           <span><?php echo $desc; ?> (<?php echo $count; ?> رکورد)</span>
           <span class="icon"><?php echo $exists ? '✅' : '❌'; ?></span>
       </div>
       <?php
   }
   ?>
   
   <h2>تنظیمات API</h2>
   <?php
   $options = get_option('adm_options', []);
   $claude_api = !empty($options['api_key']);
   $openai_api = !empty($options['openai_api_key']);
   $arvan_enabled = !empty($options['enable_arvan']);
   ?>
   
   <div class="status-item <?php echo $claude_api ? 'status-ok' : 'status-warning'; ?>">
       <span>Claude API Key</span>
       <span class="icon"><?php echo $claude_api ? '✅' : '⚠️'; ?></span>
   </div>
   
   <div class="status-item <?php echo $openai_api ? 'status-ok' : 'status-warning'; ?>">
       <span>OpenAI API Key (برای Embeddings)</span>
       <span class="icon"><?php echo $openai_api ? '✅' : '⚠️'; ?></span>
   </div>
   
   <div class="status-item <?php echo $arvan_enabled ? 'status-ok' : 'status-warning'; ?>">
       <span>ArvanCloud Integration</span>
       <span class="icon"><?php echo $arvan_enabled ? '✅' : '⚠️'; ?></span>
   </div>
   
   <h2>PHP Extensions</h2>
   <?php
   $required_extensions = [
       'pdo' => 'PDO (برای ArvanCloud)',
       'pdo_mysql' => 'PDO MySQL',
       'openssl' => 'OpenSSL (برای رمزنگاری)',
       'json' => 'JSON',
       'mbstring' => 'Multibyte String',
       'curl' => 'cURL (برای API calls)'
   ];
   
   foreach ($required_extensions as $ext => $desc) {
       $loaded = extension_loaded($ext);
       ?>
       <div class="status-item <?php echo $loaded ? 'status-ok' : 'status-error'; ?>">
           <span><?php echo $desc; ?></span>
           <span class="icon"><?php echo $loaded ? '✅' : '❌'; ?></span>
       </div>
       <?php
   }
   ?>
   
   <h2>سیستم</h2>
   <?php
   $upload_dir = wp_upload_dir();
   $writable = is_writable($upload_dir['basedir']);
   $memory_limit = ini_get('memory_limit');
   $memory_ok = intval($memory_limit) >= 128;
   $php_version_ok = version_compare(PHP_VERSION, '7.4', '>=');
   ?>
   
   <div class="status-item <?php echo $php_version_ok ? 'status-ok' : 'status-error'; ?>">
       <span>PHP Version (<?php echo PHP_VERSION; ?>)</span>
       <span class="icon"><?php echo $php_version_ok ? '✅' : '❌'; ?></span>
   </div>
   
   <div class="status-item <?php echo $memory_ok ? 'status-ok' : 'status-warning'; ?>">
       <span>Memory Limit (<?php echo $memory_limit; ?>)</span>
       <span class="icon"><?php echo $memory_ok ? '✅' : '⚠️'; ?></span>
   </div>
   
   <div class="status-item <?php echo $writable ? 'status-ok' : 'status-error'; ?>">
       <span>دسترسی نوشتن در uploads</span>
       <span class="icon"><?php echo $writable ? '✅' : '❌'; ?></span>
   </div>
   
   <h2>REST API Endpoints</h2>
   <?php
   $rest_url = get_rest_url(null, 'ai-dr-mostafazade/v1');
   $endpoints = [
       '/chat' => 'Chat endpoint',
       '/memory/search' => 'Memory search',
       '/stats' => 'Statistics',
       '/embedding' => 'Generate embeddings',
       '/addons' => 'Addon management'
   ];
   ?>
   <pre><?php
   foreach ($endpoints as $endpoint => $desc) {
       echo "POST/GET {$rest_url}{$endpoint} - {$desc}\n";
   }
   ?></pre>
   
   <h2>Addons</h2>
   <?php
   $addons_dir = ADM_PLUGIN_DIR . 'addons/';
   if (is_dir($addons_dir)) {
       $addon_folders = glob($addons_dir . '*', GLOB_ONLYDIR);
       if (!empty($addon_folders)) {
           foreach ($addon_folders as $folder) {
               $addon_name = basename($folder);
               echo '<div class="status-item status-ok">';
               echo '<span>Addon: ' . $addon_name . '</span>';
               echo '<span class="icon">📦</span>';
               echo '</div>';
           }
       } else {
           echo '<div class="status-item status-warning">';
           echo '<span>هیچ addon ای یافت نشد</span>';
           echo '<span class="icon">⚠️</span>';
           echo '</div>';
       }
   }
   ?>
   
   <h2>خلاصه</h2>
   <?php
   $all_ok = $plugin_active && 
             $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}adm_memory'") && 
             $php_version_ok && 
             $writable;
   ?>
   
   <div class="status-item <?php echo $all_ok ? 'status-ok' : 'status-warning'; ?>">
       <span>وضعیت کلی نصب</span>
       <span class="icon"><?php echo $all_ok ? '✅ آماده استفاده' : '⚠️ نیاز به تنظیمات'; ?></span>
   </div>
   
   <?php if (!$all_ok): ?>
   <h3>مراحل بعدی:</h3>
   <ol>
       <?php if (!$plugin_active): ?>
       <li>افزونه را از منوی پلاگین‌ها فعال کنید</li>
       <?php endif; ?>
       
       <?php if (!$claude_api): ?>
       <li>کلید API کلاد را در تنظیمات وارد کنید</li>
       <?php endif; ?>
       
       <?php if (!$php_version_ok): ?>
       <li>PHP را به نسخه 7.4 یا بالاتر ارتقا دهید</li>
       <?php endif; ?>
   </ol>
   <?php endif; ?>
   
   <a href="<?php echo admin_url('admin.php?page=ai-dr-mostafazade'); ?>" class="back-link">
       → رفتن به تنظیمات افزونه
   </a>
</div>
</body>
</html>
