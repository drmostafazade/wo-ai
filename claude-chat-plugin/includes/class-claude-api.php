<?php
class ClaudeAPI {
    private $apiKey;
    private $model;
    private $apiUrl = 'https://api.anthropic.com/v1/messages';
    private $apiVersion = '2023-06-01'; // Latest stable version
    
    public function __construct() {
        $this->apiKey = get_option('claude_chat_api_key');
        $this->model = get_option('claude_chat_model', 'claude-3-5-sonnet-20241022');
    }
    
    public function getAvailableModels() {
        // بر اساس مستندات رسمی Anthropic - آخرین بروزرسانی: Jan 2025
        $models = [
            // Claude 3.5 Family
            [
                'id' => 'claude-3-5-sonnet-20241022',
                'name' => 'Claude 3.5 Sonnet (Latest)',
                'family' => 'Claude 3.5',
                'description' => 'قوی‌ترین مدل با بهترین عملکرد در کدنویسی و reasoning',
                'context_window' => 200000,
                'max_output' => 8192,
                'training_cutoff' => '2024-04',
                'cost_per_million' => ['input' => 3.00, 'output' => 15.00],
                'capabilities' => ['کدنویسی پیشرفته', 'Vision', 'Function Calling', 'JSON Mode'],
                'recommended' => true
            ],
            [
                'id' => 'claude-3-5-haiku-20241022',
                'name' => 'Claude 3.5 Haiku',
                'family' => 'Claude 3.5',
                'description' => 'سریع و هوشمند، مناسب برای کارهای real-time',
                'context_window' => 200000,
                'max_output' => 8192,
                'training_cutoff' => '2024-04',
                'cost_per_million' => ['input' => 0.80, 'output' => 4.00],
                'capabilities' => ['سرعت بالا', 'کدنویسی', 'Vision', 'JSON Mode']
            ],
            
            // Claude 3 Family (Legacy)
            [
                'id' => 'claude-3-opus-20240229',
                'name' => 'Claude 3 Opus',
                'family' => 'Claude 3',
                'description' => 'مدل قدیمی‌تر اما همچنان قدرتمند',
                'context_window' => 200000,
                'max_output' => 4096,
                'training_cutoff' => '2023-08',
                'cost_per_million' => ['input' => 15.00, 'output' => 75.00],
                'capabilities' => ['تحلیل عمیق', 'کدنویسی', 'Vision'],
                'deprecated_notice' => 'توصیه می‌شود از Claude 3.5 Sonnet استفاده کنید'
            ],
            [
                'id' => 'claude-3-sonnet-20240229',
                'name' => 'Claude 3 Sonnet',
                'family' => 'Claude 3',
                'description' => 'تعادل بین سرعت و عملکرد',
                'context_window' => 200000,
                'max_output' => 4096,
                'training_cutoff' => '2023-08',
                'cost_per_million' => ['input' => 3.00, 'output' => 15.00],
                'capabilities' => ['کدنویسی', 'تحلیل', 'Vision']
            ],
            [
                'id' => 'claude-3-haiku-20240307',
                'name' => 'Claude 3 Haiku',
                'family' => 'Claude 3',
                'description' => 'سریع‌ترین مدل نسل قبل',
                'context_window' => 200000,
                'max_output' => 4096,
                'training_cutoff' => '2023-08',
                'cost_per_million' => ['input' => 0.25, 'output' => 1.25],
                'capabilities' => ['سرعت بالا', 'کارهای ساده', 'Vision']
            ]
        ];
        
        // بررسی برای مدل‌های جدید از طریق API (در آینده)
        $cached_models = get_transient('claude_models_cache');
        if ($cached_models === false) {
            // ذخیره برای 24 ساعت
            set_transient('claude_models_cache', $models, DAY_IN_SECONDS);
        }
        
        return $models;
    }
    
    public function testConnection() {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => __('کلید API تنظیم نشده است', 'claude-chat')
            ];
        }
        
        $testMessage = "Say 'Connection successful!' in one line.";
        $response = $this->sendMessage($testMessage);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => $response['message'],
                'model_used' => $response['model'] ?? $this->model,
                'usage' => $response['usage'] ?? [],
                'api_version' => $this->apiVersion
            ];
        }
        
        return $response;
    }
    
    public function sendMessage($message, $preferences = [], $systemPrompt = null) {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => __('کلید API تنظیم نشده است', 'claude-chat')
            ];
        }
        
        // استفاده از system prompt سفارشی یا ساخت آن از preferences
        if ($systemPrompt === null) {
            $systemPrompt = $this->buildSystemPrompt($preferences);
        }
        
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->apiVersion,
            'anthropic-beta' => 'messages-2023-12-15' // برای قابلیت‌های جدید
        ];
        
        $body = [
            'model' => $this->model,
            'max_tokens' => intval($preferences['max_tokens'] ?? 4096),
            'temperature' => floatval($preferences['temperature'] ?? 0.7),
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ];
        
        // اضافه کردن تنظیمات اضافی
        if (!empty($preferences['stop_sequences'])) {
            $body['stop_sequences'] = $preferences['stop_sequences'];
        }
        
        $response = wp_remote_post($this->apiUrl, [
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 120 // افزایش timeout برای پاسخ‌های طولانی
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($statusCode === 200 && isset($data['content'][0]['text'])) {
            return [
                'success' => true,
                'message' => $data['content'][0]['text'],
                'usage' => $data['usage'] ?? [],
                'model' => $data['model'] ?? $this->model,
                'stop_reason' => $data['stop_reason'] ?? null
            ];
        }
        
        // مدیریت خطاهای مختلف
        $errorMessage = __('خطای نامشخص', 'claude-chat');
        if (isset($data['error']['message'])) {
            $errorMessage = $data['error']['message'];
        } elseif ($statusCode === 401) {
            $errorMessage = __('کلید API نامعتبر است', 'claude-chat');
        } elseif ($statusCode === 429) {
            $errorMessage = __('محدودیت rate limit - لطفاً کمی صبر کنید', 'claude-chat');
        } elseif ($statusCode === 400) {
            $errorMessage = __('درخواست نامعتبر - پارامترها را بررسی کنید', 'claude-chat');
        }
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'status_code' => $statusCode
        ];
    }
    
    private function buildSystemPrompt($preferences) {
        $prompt = "You are a professional developer assistant. ";
        
        if (!empty($preferences['language'])) {
            $prompt .= "Respond in {$preferences['language']}. ";
        }
        
        if (!empty($preferences['tech_stack'])) {
            $prompt .= "The project uses: " . implode(', ', $preferences['tech_stack']) . ". ";
        }
        
        if (!empty($preferences['project_context'])) {
            $prompt .= "Project context: {$preferences['project_context']} ";
        }
        
        if (!empty($preferences['coding_style'])) {
            $prompt .= "Follow {$preferences['coding_style']} coding standards. ";
        }
        
        $prompt .= "Always provide code in executable script format. ";
        $prompt .= "Use proper markdown formatting for code blocks. ";
        $prompt .= "Be concise and focus on providing working code solutions.";
        
        return $prompt;
    }
    
    public function checkApiStatus() {
        // در حال حاضر Anthropic endpoint مستقیم برای status ندارد
        // بنابراین با یک درخواست ساده تست می‌کنیم
        $testResult = $this->testConnection();
        
        return [
            'operational' => $testResult['success'],
            'last_check' => current_time('mysql'),
            'api_version' => $this->apiVersion,
            'model_count' => count($this->getAvailableModels())
        ];
    }
}
