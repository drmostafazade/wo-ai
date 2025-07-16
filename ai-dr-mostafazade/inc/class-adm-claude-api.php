<?php
/**
* Claude API Integration
*/
class ADM_Claude_API {
   
   private $options;
   private $api_endpoint = 'https://api.anthropic.com/v1/messages';
   
   public function __construct() {
       $this->options = get_option('adm_options', []);
   }
   
   /**
    * Get available Claude models
    */
   public static function get_available_models() {
       $cached = get_transient('adm_claude_models');
       if ($cached) return $cached;
       
       // Default models
       $models = [
           ['id' => 'claude-3-5-sonnet-20240620', 'name' => 'Claude 3.5 Sonnet'],
           ['id' => 'claude-3-opus-20240229', 'name' => 'Claude 3 Opus'],
           ['id' => 'claude-3-sonnet-20240229', 'name' => 'Claude 3 Sonnet'],
           ['id' => 'claude-3-haiku-20240307', 'name' => 'Claude 3 Haiku']
       ];
       
       // Try to fetch updated list
       $response = wp_remote_get('https://raw.githubusercontent.com/anthropics/anthropic-sdk-python/main/src/anthropic/types/model.py');
       
       if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
           // Parse models from response if needed
           // For now, use default list
       }
       
       set_transient('adm_claude_models', $models, DAY_IN_SECONDS);
       return $models;
   }
   
   /**
    * Send message to Claude
    */
   public function send_message($messages, $options = []) {
       $api_key = $this->options['api_key'] ?? '';
       
       if (empty($api_key)) {
           return [
               'success' => false,
               'error' => 'کلید API تنظیم نشده است.'
           ];
       }
       
       // Get system prompt
       $system_prompt = ADM_Helpers::get_system_prompt(
           $this->options['persona'] ?? 'general',
           $this->options['persona_versions'] ?? [],
           $this->options['custom_prompt'] ?? ''
       );
       
       // Apply filters
       $system_prompt = apply_filters('adm_system_prompt', $system_prompt);
       $messages = apply_filters('adm_messages', $messages);
       
       // Build request body
       $body = [
           'model' => $this->options['model'] ?? 'claude-3-5-sonnet-20240620',
           'max_tokens' => $options['max_tokens'] ?? 4096,
           'temperature' => $options['temperature'] ?? 0.7,
           'system' => $system_prompt,
           'messages' => $messages
       ];
       
       // Add optional parameters
       if (isset($options['stop_sequences'])) {
           $body['stop_sequences'] = $options['stop_sequences'];
       }
       
       // Log request if debug mode
       ADM_Helpers::log('Sending request to Claude API', 'debug');
       
       // Send request
       $response = wp_remote_post($this->api_endpoint, [
           'headers' => [
               'x-api-key' => $api_key,
               'anthropic-version' => '2023-06-01',
               'content-type' => 'application/json'
           ],
           'body' => json_encode($body),
           'timeout' => 120,
           'data_format' => 'body'
       ]);
       
       if (is_wp_error($response)) {
           ADM_Helpers::log('API Error: ' . $response->get_error_message(), 'error');
           return [
               'success' => false,
               'error' => $response->get_error_message()
           ];
       }
       
       $response_code = wp_remote_retrieve_response_code($response);
       $response_body = wp_remote_retrieve_body($response);
       $data = json_decode($response_body, true);
       
       if ($response_code !== 200) {
           $error_message = $data['error']['message'] ?? 'خطای ناشناخته';
           ADM_Helpers::log('API Error Response: ' . $error_message, 'error');
           
           return [
               'success' => false,
               'error' => $this->translate_error($error_message)
           ];
       }
       
       // Process response
       $result = [
           'success' => true,
           'data' => $data
       ];
       
       // Apply filters
       return apply_filters('adm_api_response', $result, $messages);
   }
   
   /**
    * Stream message (for future implementation)
    */
   public function stream_message($messages, $callback) {
       // Streaming implementation for real-time responses
       // This would use Server-Sent Events or WebSocket
   }
   
   /**
    * Translate API errors to Persian
    */
   private function translate_error($error) {
       $translations = [
           'Invalid API key' => 'کلید API نامعتبر است.',
           'Rate limit exceeded' => 'محدودیت درخواست. لطفاً کمی صبر کنید.',
           'Model not found' => 'مدل انتخاب شده یافت نشد.',
           'Context length exceeded' => 'پیام بیش از حد طولانی است.',
           'Internal server error' => 'خطای سرور. لطفاً دوباره تلاش کنید.'
       ];
       
      foreach ($translations as $en => $fa) {
           if (stripos($error, $en) !== false) {
               return $fa;
           }
       }
       
       return 'خطا: ' . $error;
   }
   
   /**
    * Validate API key
    */
   public function validate_api_key($api_key = null) {
       if (!$api_key) {
           $api_key = $this->options['api_key'] ?? '';
       }
       
       if (empty($api_key)) {
           return false;
       }
       
       // Test with a simple request
       $response = wp_remote_post($this->api_endpoint, [
           'headers' => [
               'x-api-key' => $api_key,
               'anthropic-version' => '2023-06-01',
               'content-type' => 'application/json'
           ],
           'body' => json_encode([
               'model' => 'claude-3-haiku-20240307',
               'max_tokens' => 10,
               'messages' => [
                   ['role' => 'user', 'content' => 'Hi']
               ]
           ]),
           'timeout' => 10
       ]);
       
       return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
   }
}
