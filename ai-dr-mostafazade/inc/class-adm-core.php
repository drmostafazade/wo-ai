<?php
/**
* Core Plugin Class with Addon Architecture
*/
class ADM_Core {
   
   private static $instance = null;
   private $addons = [];
   
   public static function get_instance() {
       if (null === self::$instance) {
           self::$instance = new self();
       }
       return self::$instance;
   }
   
   public function __construct() {
       $this->load_dependencies();
       $this->init_hooks();
       $this->load_addons();
   }
   
   private function load_dependencies() {
       // Core classes
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-helpers.php';
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-settings.php';
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-claude-api.php';
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-vector-db.php';
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-arvan-cloud.php';
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-ajax-handler.php';
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-shortcode.php';
       require_once ADM_PLUGIN_DIR . 'inc/class-adm-addon-loader.php';
       
       // Initialize components
       new ADM_Settings();
       new ADM_Ajax_Handler();
       new ADM_Shortcode();
   }
   
   private function init_hooks() {
       // Core hooks for addons
       add_action('adm_init', [$this, 'init']);
       add_filter('adm_memory_context', [$this, 'filter_memory_context'], 10, 2);
       add_action('adm_after_message_save', [$this, 'after_message_save'], 10, 2);
       
       // Assets
       add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
       add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
   }
   
   private function load_addons() {
       $addon_loader = new ADM_Addon_Loader();
       $this->addons = $addon_loader->load_all();
       
       // Fire addon loaded action
       do_action('adm_addons_loaded', $this->addons);
   }
   
   public function enqueue_scripts() {
       if (is_singular() && has_shortcode(get_post()->post_content, 'ai_dr_chat')) {
           // CodeMirror 6
           wp_enqueue_script('codemirror', ADM_PLUGIN_URL . 'assets/lib/codemirror/codemirror.min.js', [], '6.0.1');
           wp_enqueue_style('codemirror', ADM_PLUGIN_URL . 'assets/lib/codemirror/codemirror.min.css', [], '6.0.1');
           
           // Custom styles and scripts
           wp_enqueue_style('adm-chat', ADM_PLUGIN_URL . 'assets/css/chat.css', [], ADM_VERSION);
           wp_enqueue_script('adm-chat', ADM_PLUGIN_URL . 'assets/js/chat.js', ['jquery', 'codemirror'], ADM_VERSION, true);
           
           wp_localize_script('adm-chat', 'adm_ajax', [
               'url' => admin_url('admin-ajax.php'),
               'nonce' => wp_create_nonce('adm-chat-nonce')
           ]);
       }
   }
   
   public function admin_scripts($hook) {
       if (strpos($hook, 'ai-dr-mostafazade') !== false) {
           wp_enqueue_style('adm-admin', ADM_PLUGIN_URL . 'assets/css/admin.css', [], ADM_VERSION);
           wp_enqueue_script('adm-admin', ADM_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], ADM_VERSION, true);
           
           wp_localize_script('adm-admin', 'adm_admin_ajax', [
               'url' => admin_url('admin-ajax.php'),
               'nonce' => wp_create_nonce('adm-admin-nonce')
           ]);
       }
   }
   
   public static function activate() {
       // Run migrations
       ADM_DB_Migration::run();
       
       // Create default options
       $defaults = [
           'api_key' => '',
           'openai_api_key' => '',
           'model' => 'claude-3-5-sonnet-20240620',
           'persona' => 'general',
           'enable_memory' => 1,
           'enable_embeddings' => 0,
           'enable_arvan' => 0,
           'arvan_config' => []
       ];
       
       $existing = get_option('adm_options', []);
       update_option('adm_options', array_merge($defaults, $existing));
       
       // Flush rewrite rules
       flush_rewrite_rules();
   }
}
