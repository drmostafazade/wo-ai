<?php
/**
 * Vector Database Implementation with Fixed OpenAI Integration
 */
class ADM_Vector_DB {
    
    private $openai_api_key;
    private $embedding_model = 'text-embedding-ada-002';
    private $dimension = 1536;
    
    public function __construct() {
        $options = get_option('adm_options', []);
        $this->openai_api_key = $options['openai_api_key'] ?? '';
    }
    
    /**
     * Generate embedding for text - FIXED VERSION
     */
    public function generate_embedding($text) {
        if (empty($this->openai_api_key)) {
            error_log('ADM Vector DB: No OpenAI API key configured');
            return false;
        }
        
        if (empty($text)) {
            error_log('ADM Vector DB: Empty text provided');
            return false;
        }
        
        // Prepare request
        $request_body = json_encode([
            'model' => $this->embedding_model,
            'input' => $text
        ]);
        
        $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openai_api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => $request_body,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            error_log('ADM Vector DB Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('ADM Vector DB API Error: HTTP ' . $response_code);
            error_log('Response body: ' . wp_remote_retrieve_body($response));
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['data'][0]['embedding']) || !is_array($data['data'][0]['embedding'])) {
            error_log('ADM Vector DB: Invalid API response structure');
            error_log('Response: ' . $body);
            return false;
        }
        
        return $data['data'][0]['embedding'];
    }
    
    /**
     * Save embedding to database - FIXED VERSION
     */
    public function save_embedding($memory_id, $embedding, $cluster_id = null) {
        global $wpdb;
        
        if (!is_array($embedding)) {
            error_log('ADM Vector DB: Invalid embedding format');
            return false;
        }
        
        $embedding_json = json_encode($embedding);
        if (!$embedding_json) {
            error_log('ADM Vector DB: Failed to encode embedding');
            return false;
        }
        
        return $wpdb->insert(
            $wpdb->prefix . 'adm_embeddings',
            [
                'memory_id' => $memory_id,
                'embedding_vector' => $embedding_json,
                'model' => $this->embedding_model,
                'dimension' => count($embedding),
                'cluster_id' => $cluster_id
            ],
            ['%d', '%s', '%s', '%d', '%d']
        );
    }
    
    // ... rest of the methods remain the same ...
}
