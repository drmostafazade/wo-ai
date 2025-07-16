<?php
/**
* Uninstall Script
* 
* This file is executed when the plugin is uninstalled
*/

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
   exit;
}

// Check user capabilities
if (!current_user_can('activate_plugins')) {
   return;
}

// Get options to check if data should be removed
$options = get_option('adm_options', []);
$remove_data = isset($options['remove_data_on_uninstall']) && $options['remove_data_on_uninstall'];

if ($remove_data) {
   global $wpdb;
   
   // Remove all plugin tables
   $tables = [
       'adm_memory',
       'adm_embeddings',
       'adm_clusters',
       'adm_contexts',
       'adm_feedback',
       'adm_arvan_sync'
   ];
   
   foreach ($tables as $table) {
       $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
   }
   
   // Remove options
   delete_option('adm_options');
   delete_option('adm_db_version');
   delete_option('adm_active_addons');
   
   // Remove transients
   delete_transient('adm_claude_models');
   
   // Clear any scheduled events
   wp_clear_scheduled_hook('adm_sync_memory');
   wp_clear_scheduled_hook('adm_update_clusters');
}
