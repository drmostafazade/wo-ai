<?php
/**
* REST API Endpoints
*/
class ADM_REST_API {
   
   private $namespace = 'ai-dr-mostafazade/v1';
   
   public function __construct() {
       add_action('rest_api_init', [$this, 'register_routes']);
   }
   
   /**
    * Register REST API routes
    */
   public function register_routes() {
       // Chat endpoint
       register_rest_route($this->namespace, '/chat', [
           'methods' => 'POST',
           'callback' => [$this, 'handle_chat'],
           'permission_callback' => [$this, 'check_permission'],
           'args' => [
               'message' => [
                   'required' => true,
                   'type' => 'string',
                   'sanitize_callback' => 'sanitize_textarea_field'
               ],
               'context' => [
                   'type' => 'array',
                   'default' => []
               ]
           ]
       ]);
       
       // Memory search endpoint
       register_rest_route($this->namespace, '/memory/search', [
           'methods' => 'GET',
           'callback' => [$this, 'search_memory'],
           'permission_callback' => [$this, 'check_permission'],
           'args' => [
               'query' => [
                   'required' => true,
                   'type' => 'string',
                   'sanitize_callback' => 'sanitize_text_field'
               ],
               'limit' => [
                   'type' => 'integer',
                   'default' => 5,
                   'minimum' => 1,
                   'maximum' => 20
               ]
           ]
       ]);
       
       // Stats endpoint
       register_rest_route($this->namespace, '/stats', [
           'methods' => 'GET',
           'callback' => [$this, 'get_stats'],
           'permission_callback' => [$this, 'check_permission']
       ]);
       
       // Embedding endpoint
       register_rest_route($this->namespace, '/embedding', [
           'methods' => 'POST',
           'callback' => [$this, 'generate_embedding'],
           'permission_callback' => [$this, 'check_permission'],
           'args' => [
               'text' => [
                   'required' => true,
                   'type' => 'string'
               ]
           ]
       ]);
       
       // Addon endpoints
       register_rest_route($this->namespace, '/addons', [
           'methods' => 'GET',
           'callback' => [$this, 'get_addons'],
           'permission_callback' => [$this, 'check_permission']
       ]);
       
       register_rest_route($this->namespace, '/addons/(?P<slug>[a-z0-9-]+)', [
           'methods' => 'POST',
           'callback' => [$this, 'toggle_addon'],
           'permission_callback' => [$this, 'check_permission'],
           'args' => [
               'action' => [
                   'required' => true,
                   'enum' => ['activate', 'deactivate']
               ]
           ]
       ]);
   }
   
   /**
    * Check permission for API access
    */
   public function check_permission($request) {
       // Check for API key in header
       $api_key = $request->get_header('X-API-Key');
       
       if ($api_key) {
           $options = get_option('adm_options', []);
           $stored_api_key = $options['rest_api_key'] ?? '';
           
           if ($api_key === $stored_api_key) {
               return true;
           }
       }
       
       // Fall back to WordPress authentication
       return current_user_can('manage_options');
   }
   
   /**
    * Handle chat endpoint
    */
   public function handle_chat($request) {
       $message = $request->get_param('message');
       $context = $request->get_param('context');
       
       $handler = new ADM_Ajax_Handler();
       
       // Simulate AJAX request
       $_POST['message'] = $message;
       $_POST['context_files'] = $context;
       $_POST['nonce'] = wp_create_nonce('adm-chat-nonce');
       
       // Process message
       ob_start();
       $handler->handle_chat();
       $response = ob_get_clean();
       
       return json_decode($response, true);
   }
   
   /**
    * Search memory
    */
   public function search_memory($request) {
       $query = $request->get_param('query');
       $limit = $request->get_param('limit');
       
       $vector_db = new ADM_Vector_DB();
       $options = get_option('adm_options', []);
       
       if (!empty($options['enable_embeddings'])) {
           // Generate embedding for query
           $embedding = $vector_db->generate_embedding($query);
           
           if ($embedding) {
               $results = $vector_db->find_similar($embedding, $limit);
               
               return new WP_REST_Response([
                   'success' => true,
                   'results' => $results,
                   'method' => 'vector_search'
               ], 200);
           }
       }
       
       // Fallback to keyword search
       global $wpdb;
       $like = '%' . $wpdb->esc_like($query) . '%';
       
       $results = $wpdb->get_results($wpdb->prepare(
           "SELECT id, user_query, ai_response, created_at 
            FROM {$wpdb->prefix}adm_memory 
            WHERE user_query LIKE %s OR ai_response LIKE %s 
            ORDER BY created_at DESC 
            LIMIT %d",
           $like, $like, $limit
       ));
       
       return new WP_REST_Response([
           'success' => true,
           'results' => $results,
           'method' => 'keyword_search'
       ], 200);
   }
   
   /**
    * Get statistics
    */
   public function get_stats($request) {
       global $wpdb;
       
       $stats = [
           'memories' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_memory"),
           'embeddings' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings"),
           'clusters' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_clusters"),
           'contexts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_contexts"),
           'feedback' => [
               'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_feedback"),
               'positive' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_feedback WHERE rating >= 4"),
               'negative' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_feedback WHERE rating <= 2")
           ],
           'sync' => [
               'synced' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_arvan_sync WHERE sync_status = 'synced'"),
               'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_arvan_sync WHERE sync_status = 'pending'"),
               'failed' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_arvan_sync WHERE sync_status = 'failed'")
           ]
       ];
       
       return new WP_REST_Response($stats, 200);
   }
   
   /**
    * Generate embedding for text
    */
   public function generate_embedding($request) {
       $text = $request->get_param('text');
       
       $vector_db = new ADM_Vector_DB();
       $embedding = $vector_db->generate_embedding($text);
       
       if ($embedding) {
           return new WP_REST_Response([
               'success' => true,
               'embedding' => $embedding,
               'dimension' => count($embedding)
           ], 200);
       }
       
       return new WP_ERROR('embedding_failed', 'Failed to generate embedding', ['status' => 500]);
   }
   
   /**
    * Get addons list
    */
   public function get_addons($request) {
       $loader = new ADM_Addon_Loader();
       $addons = $loader->get_available_addons();
       
       return new WP_REST_Response($addons, 200);
   }
   
   /**
    * Toggle addon
    */
   public function toggle_addon($request) {
       $slug = $request->get_param('slug');
       $action = $request->get_param('action');
       
       $loader = new ADM_Addon_Loader();
       
       if ($action === 'activate') {
           $result = $loader->activate_addon($slug);
       } else {
           $result = $loader->deactivate_addon($slug);
       }
       
       if ($result) {
           return new WP_REST_Response([
               'success' => true,
               'message' => $action === 'activate' ? 'Addon activated' : 'Addon deactivated'
           ], 200);
       }
       
       return new WP_ERROR('addon_action_failed', 'Failed to ' . $action . ' addon', ['status' => 500]);
   }
}

// Initialize REST API
new ADM_REST_API();
