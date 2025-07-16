<?php
/**
* Addon Loader and Management System
*/
class ADM_Addon_Loader {
   
   private $addons = [];
   private $addon_dir;
   
   public function __construct() {
       $this->addon_dir = ADM_PLUGIN_DIR . 'addons/';
       $this->discover_addons();
   }
   
   /**
    * Discover and load all addons
    */
   public function load_all() {
       foreach ($this->addons as $addon_slug => $addon_info) {
           if ($this->is_addon_active($addon_slug)) {
               $this->load_addon($addon_slug);
           }
       }
       
       return $this->addons;
   }
   
   /**
    * Discover available addons
    */
   private function discover_addons() {
       if (!is_dir($this->addon_dir)) {
           return;
       }
       
       $addon_folders = glob($this->addon_dir . '*', GLOB_ONLYDIR);
       
       foreach ($addon_folders as $folder) {
           $addon_file = $folder . '/addon.php';
           
           if (file_exists($addon_file)) {
               $addon_data = $this->get_addon_data($addon_file);
               
               if ($addon_data) {
                   $slug = basename($folder);
                   $this->addons[$slug] = array_merge($addon_data, [
                       'slug' => $slug,
                       'file' => $addon_file,
                       'active' => $this->is_addon_active($slug)
                   ]);
               }
           }
       }
   }
   
   /**
    * Get addon header data
    */
   private function get_addon_data($file) {
       $headers = [
           'name' => 'Addon Name',
           'description' => 'Description',
           'version' => 'Version',
           'author' => 'Author',
           'requires' => 'Requires'
       ];
       
       $data = get_file_data($file, $headers);
       
       if (empty($data['name'])) {
           return false;
       }
       
       return $data;
   }
   
   /**
    * Load a specific addon
    */
   private function load_addon($slug) {
       if (isset($this->addons[$slug])) {
           require_once $this->addons[$slug]['file'];
           
           // Initialize addon class if exists
           $class_name = 'ADM_Addon_' . str_replace('-', '_', ucwords($slug, '-'));
           
           if (class_exists($class_name)) {
               new $class_name();
           }
           
           // Fire addon loaded action
           do_action('adm_addon_loaded', $slug, $this->addons[$slug]);
       }
   }
   
   /**
    * Check if addon is active
    */
   private function is_addon_active($slug) {
       $active_addons = get_option('adm_active_addons', []);
       return in_array($slug, $active_addons);
   }
   
   /**
    * Activate an addon
    */
   public function activate_addon($slug) {
       if (!isset($this->addons[$slug])) {
           return false;
       }
       
       $active_addons = get_option('adm_active_addons', []);
       
       if (!in_array($slug, $active_addons)) {
           $active_addons[] = $slug;
           update_option('adm_active_addons', $active_addons);
           
           // Fire activation hook
           do_action('adm_addon_activated', $slug);
           
           return true;
       }
       
       return false;
   }
   
   /**
    * Deactivate an addon
    */
   public function deactivate_addon($slug) {
       $active_addons = get_option('adm_active_addons', []);
       $key = array_search($slug, $active_addons);
       
       if ($key !== false) {
           unset($active_addons[$key]);
           update_option('adm_active_addons', array_values($active_addons));
           
           // Fire deactivation hook
           do_action('adm_addon_deactivated', $slug);
           
           return true;
       }
       
       return false;
   }
   
   /**
    * Get available addons
    */
   public function get_available_addons() {
       return $this->addons;
   }
   
   /**
    * Register addon hooks
    */
   public static function register_hooks() {
       // Allow addons to register their own hooks
       add_action('adm_register_addon_hooks', function($addon_slug) {
           do_action("adm_addon_{$addon_slug}_register_hooks");
       });
       
       // Memory enhancement hooks
       add_filter('adm_memory_contexts', function($contexts, $query) {
           return apply_filters('adm_addon_memory_contexts', $contexts, $query);
       }, 10, 2);
       
       // Chat enhancement hooks
       add_filter('adm_chat_message', function($message) {
           return apply_filters('adm_addon_chat_message', $message);
       });
       
       // UI enhancement hooks
       add_action('adm_chat_toolbar', function() {
           do_action('adm_addon_chat_toolbar');
       });
       
       add_action('adm_chat_sidebar', function() {
           do_action('adm_addon_chat_sidebar');
       });
   }
}

// Initialize addon hooks
ADM_Addon_Loader::register_hooks();
