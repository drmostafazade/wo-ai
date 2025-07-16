<?php
/**
 * Enhanced AJAX Handler with Vector Database Support
 */
class ADM_Ajax_Handler {
    
    private $vector_db;
    private $arvan_cloud;
    
    public function __construct() {
        add_action('wp_ajax_adm_send_chat', [$this, 'handle_chat']);
        add_action('wp_ajax_adm_clear_memory', [$this, 'clear_memory']);
        add_action('wp_ajax_adm_rate_response', [$this, 'rate_response']);
        add_action('wp_ajax_adm_upload_context', [$this, 'upload_context']);
        add_action('wp_ajax_adm_sync_arvan', [$this, 'sync_arvan']);
        add_action('wp_ajax_adm_get_stats', [$this, 'get_stats']);
        
        $this->vector_db = new ADM_Vector_DB();
        $this->arvan_cloud = new ADM_ArvanCloud();
    }
    
    /**
     * Enhanced chat handler with vector search
     */
    public function handle_chat() {
        check_ajax_referer('adm-chat-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'دسترسی غیرمجاز.']);
        }
        
        $opts = get_option('adm_options', []);
        $api = new ADM_Claude_API();
        
        $user_message = sanitize_textarea_field(stripslashes($_POST['message']));
        $context_files = $_POST['context_files'] ?? [];
        
        // Generate embedding if enabled
        $query_embedding = null;
        if (!empty($opts['enable_embeddings']) && !empty($opts['openai_api_key'])) {
            $query_embedding = $this->vector_db->generate_embedding($user_message);
        }
        
        // Build context from multiple sources
        $context = $this->build_enhanced_context($user_message, $query_embedding, $context_files);
        
        // Prepare message with context
        $final_message = '';
        if (!empty($context)) {
            $final_message = "=== Context from Previous Conversations ===\n" . $context . "\n\n";
        }
        
        $final_message .= "=== Current User Query ===\n" . $user_message;
        
        // Add file contexts if any
        if (!empty($context_files)) {
            $file_context = $this->get_file_contexts($context_files);
            if ($file_context) {
                $final_message .= "\n\n=== File Context ===\n" . $file_context;
            }
        }
        
        $messages = [
            ['role' => 'user', 'content' => [['type' => 'text', 'text' => $final_message]]]
        ];
        
        $response = $api->send_message($messages);
        
        if ($response['success']) {
            // Save to local database
            global $wpdb;
            $table_name = $wpdb->prefix . 'adm_memory';
            
            $wpdb->insert($table_name, [
                'user_query' => $user_message,
                'ai_response' => $response['data']['content'][0]['text']
            ]);
            
            $memory_id = $wpdb->insert_id;
            
            // Save embedding if generated
            if ($query_embedding) {
                $this->vector_db->save_embedding($memory_id, $query_embedding);
            }
            
            // Save file contexts
            if (!empty($context_files)) {
                $this->save_file_contexts($memory_id, $context_files);
            }
            
            // Sync to ArvanCloud if enabled
            if (!empty($opts['enable_arvan'])) {
                wp_schedule_single_event(time() + 5, 'adm_sync_memory', [$memory_id]);
            }
            
            // Return response with memory ID for feedback
            $response['data']['memory_id'] = $memory_id;
        }
        
        wp_send_json($response);
    }
    
    /**
     * Build enhanced context using multiple strategies
     */
    private function build_enhanced_context($query, $embedding = null, $files = []) {
        $contexts = [];
        $opts = get_option('adm_options', []);
        
        // 1. Vector similarity search (if embedding available)
        if ($embedding && !empty($opts['enable_embeddings'])) {
            $similar_memories = $this->vector_db->find_similar($embedding, 3, 0.75);
            
            foreach ($similar_memories as $memory) {
                $contexts[] = sprintf(
                    "[Similar conversation - Score: %.2f]\nQ: %s\nA: %s",
                    $memory['similarity'],
                    $memory['user_query'],
                    substr($memory['ai_response'], 0, 500) . '...'
                );
            }
        }
        
        // 2. Keyword-based search (fallback)
        if (empty($contexts)) {
            $keyword_context = $this->get_keyword_context($query);
            if (!empty($keyword_context)) {
                $contexts[] = $keyword_context;
            }
        }
        
        // 3. Recent context (last 2 conversations)
        $recent = $this->get_recent_context(2);
        if ($recent) {
            $contexts[] = "[Recent conversations]\n" . $recent;
        }
        
        // 4. ArvanCloud remote search
        if (!empty($opts['enable_arvan'])) {
            $remote_results = $this->arvan_cloud->search_remote($query, 2);
            foreach ($remote_results as $result) {
                $contexts[] = sprintf(
                    "[Remote memory]\nQ: %s\nA: %s",
                    $result['user_query'],
                    substr($result['ai_response'], 0, 300) . '...'
                );
            }
        }
        
        // Filter and combine contexts
        $contexts = array_filter($contexts);
        
        // Apply addon filters
        $contexts = apply_filters('adm_memory_contexts', $contexts, $query);
        
        return implode("\n\n---\n\n", array_slice($contexts, 0, 5));
    }
    
    /**
     * Get keyword-based context
     */
    private function get_keyword_context($query) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adm_memory';
        
        $keywords = preg_split('/[\s,]+/', $query);
        $likes = [];
        
        foreach ($keywords as $keyword) {
            if (mb_strlen($keyword) > 3) {
                $likes[] = $wpdb->prepare("user_query LIKE %s", '%' . $wpdb->esc_like($keyword) . '%');
            }
        }
        
        if (empty($likes)) return '';
        
        $results = $wpdb->get_results(
            "SELECT user_query, ai_response FROM `{$table_name}` 
             WHERE " . implode(' OR ', $likes) . " 
             ORDER BY id DESC LIMIT 3"
        );
        
        if (empty($results)) return '';
        
        $context = "";
        foreach ($results as $row) {
            $context .= sprintf(
                "Q: %s\nA: %s\n\n",
                $row->user_query,
                substr($row->ai_response, 0, 400) . '...'
            );
        }
        
        return $context;
    }
    
    /**
     * Get recent conversation context
     */
    private function get_recent_context($limit = 2) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adm_memory';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT user_query, ai_response FROM `{$table_name}` 
             ORDER BY id DESC LIMIT %d",
            $limit
        ));
        
        if (empty($results)) return '';
        
        $context = "";
        foreach (array_reverse($results) as $row) {
            $context .= sprintf(
                "Q: %s\nA: %s\n\n",
                $row->user_query,
                substr($row->ai_response, 0, 300) . '...'
            );
        }
        
        return $context;
    }
    
    /**
     * Get file contexts
     */
    private function get_file_contexts($files) {
        $contexts = [];
        
        foreach ($files as $file) {
            if (isset($file['content'])) {
                $contexts[] = sprintf(
                    "File: %s\nContent: %s",
                    $file['name'],
                    substr($file['content'], 0, 500) . '...'
                );
            }
        }
        
        return implode("\n\n", $contexts);
    }
    
    /**
     * Save file contexts
     */
    private function save_file_contexts($memory_id, $files) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adm_contexts';
        
        foreach ($files as $file) {
            $wpdb->insert($table_name, [
                'memory_id' => $memory_id,
                'context_type' => 'file',
                'file_path' => $file['name'],
                'code_snippet' => $file['content'],
                'metadata' => json_encode([
                    'type' => $file['type'],
                    'size' => $file['size']
                ])
            ]);
        }
    }
    
    /**
     * Handle response rating
     */
    public function rate_response() {
        check_ajax_referer('adm-chat-nonce', 'nonce');
        
        $memory_id = intval($_POST['memory_id']);
        $rating = intval($_POST['rating']); // 1-5
        $helpful = $_POST['helpful'] === 'true';
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'adm_feedback',
            [
                'memory_id' => $memory_id,
                'rating' => $rating,
                'helpful' => $helpful,
                'user_id' => get_current_user_id()
            ]
        );
        
        // Update embedding weight based on feedback
        if ($rating >= 4) {
            // Positive feedback - increase weight
            do_action('adm_positive_feedback', $memory_id, $rating);
        }
        
        wp_send_json_success(['message' => 'بازخورد شما ثبت شد.']);
    }
    
    /**
     * Clear memory with options
     */
    public function clear_memory() {
        check_ajax_referer('adm-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'دسترسی غیرمجاز.']);
        }
        
        $clear_type = $_POST['clear_type'] ?? 'all';
        
        global $wpdb;
        
        switch ($clear_type) {
            case 'local':
                $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}adm_memory`");
                break;
                
            case 'embeddings':
                $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}adm_embeddings`");
                $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}adm_clusters`");
                break;
                
            case 'all':
                $tables = ['adm_memory', 'adm_embeddings', 'adm_clusters', 'adm_contexts', 'adm_feedback'];
                foreach ($tables as $table) {
                    $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}{$table}`");
                }
                break;
        }
        
        wp_send_json_success(['message' => 'حافظه با موفقیت پاک شد.']);
    }
    
    /**
     * Handle file upload for context
     */
    public function upload_context() {
        check_ajax_referer('adm-chat-nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['error' => 'دسترسی غیرمجاز.']);
        }
        
        $uploaded_files = [];
        
        if (!empty($_FILES['context_files'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            $files = $_FILES['context_files'];
            
            for ($i = 0; $i < count($files['name']); $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                // Check file type
                $allowed_types = ['text/plain', 'text/csv', 'application/json', 'text/markdown'];
                if (!in_array($file['type'], $allowed_types)) {
                    continue;
                }
                
                // Read file content
                $content = file_get_contents($file['tmp_name']);
                
                $uploaded_files[] = [
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'content' => $content,
                    'size' => $file['size']
                ];
            }
        }
        
        wp_send_json_success(['files' => $uploaded_files]);
    }
    
    /**
     * Sync specific memory to ArvanCloud
     */
    public function sync_arvan() {
        check_ajax_referer('adm-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'دسترسی غیرمجاز.']);
        }
        
        $memory_id = intval($_POST['memory_id'] ?? 0);
        
        if ($memory_id) {
            do_action('adm_sync_memory', $memory_id);
            wp_send_json_success(['message' => 'همگام‌سازی شروع شد.']);
        }
        
        wp_send_json_error(['error' => 'شناسه نامعتبر.']);
    }
    
    /**
     * Get statistics
     */
    public function get_stats() {
        check_ajax_referer('adm-chat-nonce', 'nonce');
        
        global $wpdb;
        
        $stats = [
            'memories' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_memory"),
            'embeddings' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings"),
            'clusters' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_clusters")
        ];
        
        wp_send_json_success($stats);
    }
}

// Hook for async sync
add_action('adm_sync_memory', function($memory_id) {
    global $wpdb;
    
    $memory = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}adm_memory WHERE id = %d",
        $memory_id
    ), ARRAY_A);
    
    if ($memory) {
        // Get embedding if exists
        $embedding = $wpdb->get_var($wpdb->prepare(
            "SELECT embedding_vector FROM {$wpdb->prefix}adm_embeddings WHERE memory_id = %d",
            $memory_id
        ));
        
        $memory['embedding'] = $embedding;
        
        $arvan = new ADM_ArvanCloud();
        $arvan->sync_memory($memory_id, $memory);
    }
});
