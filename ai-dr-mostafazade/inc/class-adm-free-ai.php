<?php
/**
 * Free AI API Integration
 * Supports: Hugging Face, Cohere, and other free APIs
 */
class ADM_Free_AI {
    
    /**
     * Generate embedding using Hugging Face (FREE)
     */
    public function generate_embedding_huggingface($text, $api_token) {
        $model = 'sentence-transformers/all-MiniLM-L6-v2';
        
        $response = wp_remote_post("https://api-inference.huggingface.co/models/{$model}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'inputs' => $text
            ]),
            'timeout' => 30
        ]);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return $body; // Returns array of floats
        }
        
        return false;
    }
    
    /**
     * Use local PHP-ML for embeddings (No API needed)
     */
    public function generate_embedding_local($text) {
        // Simple TF-IDF based embedding
        $words = str_word_count(strtolower($text), 1);
        $unique_words = array_unique($words);
        
        // Create a simple vector (this is a basic example)
        $vector = [];
        foreach ($unique_words as $word) {
            $count = array_count_values($words)[$word];
            $vector[] = $count / count($words); // Simple TF
        }
        
        // Pad to fixed size
        $target_size = 384;
        while (count($vector) < $target_size) {
            $vector[] = 0;
        }
        
        return array_slice($vector, 0, $target_size);
    }
}
