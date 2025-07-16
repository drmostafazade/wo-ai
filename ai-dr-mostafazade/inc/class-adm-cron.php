<?php
/**
* Cron Job Handler
*/
class ADM_Cron {
   
   public function __construct() {
       add_action('init', [$this, 'schedule_events']);
       
       // Cron hooks
       add_action('adm_daily_maintenance', [$this, 'daily_maintenance']);
       add_action('adm_hourly_sync', [$this, 'hourly_sync']);
       add_action('adm_weekly_optimization', [$this, 'weekly_optimization']);
   }
   
   /**
    * Schedule cron events
    */
   public function schedule_events() {
       // Daily maintenance
       if (!wp_next_scheduled('adm_daily_maintenance')) {
           wp_schedule_event(time(), 'daily', 'adm_daily_maintenance');
       }
       
       // Hourly sync (if ArvanCloud enabled)
       $options = get_option('adm_options', []);
       if (!empty($options['enable_arvan'])) {
           if (!wp_next_scheduled('adm_hourly_sync')) {
               wp_schedule_event(time(), 'hourly', 'adm_hourly_sync');
           }
       }
       
       // Weekly optimization
       if (!wp_next_scheduled('adm_weekly_optimization')) {
           wp_schedule_event(time(), 'weekly', 'adm_weekly_optimization');
       }
   }
   
   /**
    * Daily maintenance tasks
    */
   public function daily_maintenance() {
       global $wpdb;
       
       // Clean old feedback entries (older than 90 days)
       $wpdb->query(
           "DELETE FROM {$wpdb->prefix}adm_feedback 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)"
       );
       
       // Update clusters if enough new embeddings
       $new_embeddings = $wpdb->get_var(
           "SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings 
            WHERE cluster_id IS NULL"
       );
       
       if ($new_embeddings > 100) {
           $vector_db = new ADM_Vector_DB();
           $vector_db->update_clusters();
       }
       
       // Log maintenance
       ADM_Helpers::log('Daily maintenance completed', 'info');
   }
   
   /**
    * Hourly sync with ArvanCloud
    */
   public function hourly_sync() {
       global $wpdb;
       
       // Get unsynced memories
       $unsynced = $wpdb->get_results(
           "SELECT m.* FROM {$wpdb->prefix}adm_memory m
            LEFT JOIN {$wpdb->prefix}adm_arvan_sync s ON m.id = s.local_id
            WHERE s.id IS NULL OR s.sync_status = 'failed'
            LIMIT 50"
       );
       
       if (empty($unsynced)) {
           return;
       }
       
       $arvan = new ADM_ArvanCloud();
       $synced = 0;
       
       foreach ($unsynced as $memory) {
           // Get embedding if exists
           $embedding = $wpdb->get_var($wpdb->prepare(
               "SELECT embedding_vector FROM {$wpdb->prefix}adm_embeddings 
                WHERE memory_id = %d",
               $memory->id
           ));
           
           $data = [
               'user_query' => $memory->user_query,
               'ai_response' => $memory->ai_response,
               'embedding' => $embedding,
               'created_at' => $memory->created_at
           ];
           
           if ($arvan->sync_memory($memory->id, $data)) {
               $synced++;
           }
       }
       
       ADM_Helpers::log("Synced {$synced} memories to ArvanCloud", 'info');
   }
   
   /**
    * Weekly optimization
    */
   public function weekly_optimization() {
       global $wpdb;
       
       // Optimize tables
       $tables = [
           'adm_memory',
           'adm_embeddings',
           'adm_clusters',
           'adm_contexts',
           'adm_feedback',
           'adm_arvan_sync'
       ];
       
       foreach ($tables as $table) {
           $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}{$table}");
       }
       
       // Clean orphaned embeddings
       $wpdb->query(
           "DELETE e FROM {$wpdb->prefix}adm_embeddings e
            LEFT JOIN {$wpdb->prefix}adm_memory m ON e.memory_id = m.id
            WHERE m.id IS NULL"
       );
       
       // Recalculate cluster statistics
       $wpdb->query(
           "UPDATE {$wpdb->prefix}adm_clusters c
            SET items_count = (
                SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings e
                WHERE e.cluster_id = c.cluster_id
            )"
       );
       
       ADM_Helpers::log('Weekly optimization completed', 'info');
   }
}

// Initialize cron handler
new ADM_Cron();
