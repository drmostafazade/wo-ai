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

$options = get_option('adm_options', []);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>Test OpenAI API</title>
    <style>
        body { font-family: Tahoma, Arial; direction: rtl; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 15px; direction: ltr; text-align: left; }
    </style>
</head>
<body>
    <h1>تست OpenAI API</h1>
    
    <?php
    if (empty($options['openai_api_key'])) {
        echo '<div class="error">❌ کلید OpenAI API تنظیم نشده است!</div>';
        die();
    }
    
    echo '<p>در حال تست API...</p>';
    
    // Test API
    $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
        'headers' => [
            'Authorization' => 'Bearer ' . $options['openai_api_key'],
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'text-embedding-ada-002',
            'input' => 'Hello, this is a test'
        ]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        echo '<div class="error">❌ خطا: ' . $response->get_error_message() . '</div>';
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        echo '<p>Status Code: ' . $code . '</p>';
        
        if ($code === 200) {
            $data = json_decode($body, true);
            if (isset($data['data'][0]['embedding'])) {
                echo '<div class="success">✅ API کار می‌کند!</div>';
                echo '<p>Embedding dimension: ' . count($data['data'][0]['embedding']) . '</p>';
            }
        } else {
            echo '<div class="error">❌ خطای API:</div>';
            echo '<pre>' . htmlspecialchars($body) . '</pre>';
        }
    }
    ?>
</body>
</html>
