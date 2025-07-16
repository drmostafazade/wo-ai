<?php
/**
* Database Migration Class
* Safely adds new tables while preserving existing data
*/
class ADM_DB_Migration {
   
   public static function run() {
       global $wpdb;
       $charset_collate = $wpdb->get_charset_collate();
       
       // Check if main table exists (preserve it)
       $main_table = $wpdb->prefix . 'adm_memory';
       if ($wpdb->get_var("SHOW TABLES LIKE '{$main_table}'") != $main_table) {
           // Create main table if not exists
           $sql = "CREATE TABLE IF NOT EXISTS `{$main_table}` (
               `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
               `user_query` TEXT NOT NULL,
               `ai_response` LONGTEXT NOT NULL,
               `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
               PRIMARY KEY (`id`),
               FULLTEXT KEY `user_query` (`user_query`)
           ) {$charset_collate};";
           
           require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
           dbDelta($sql);
       }
       
       // New tables for enhanced semantic memory
       $tables = [
           'adm_embeddings' => "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}adm_embeddings` (
               `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
               `memory_id` BIGINT(20) NOT NULL,
               `embedding_vector` TEXT NOT NULL,
               `model` VARCHAR(50) DEFAULT 'text-embedding-ada-002',
               `dimension` INT DEFAULT 1536,
               `cluster_id` INT DEFAULT NULL,
               `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (`id`),
               KEY `memory_id` (`memory_id`),
               KEY `cluster_id` (`cluster_id`),
               FOREIGN KEY (`memory_id`) REFERENCES `{$main_table}`(`id`) ON DELETE CASCADE
           ) {$charset_collate};",
           
           'adm_clusters' => "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}adm_clusters` (
               `cluster_id` INT NOT NULL AUTO_INCREMENT,
               `centroid` TEXT NOT NULL,
               `items_count` INT DEFAULT 0,
               `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               PRIMARY KEY (`cluster_id`)
           ) {$charset_collate};",
           
           'adm_contexts' => "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}adm_contexts` (
               `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
               `memory_id` BIGINT(20) NOT NULL,
               `context_type` VARCHAR(50) NOT NULL,
               `file_path` VARCHAR(255) DEFAULT NULL,
               `code_snippet` TEXT DEFAULT NULL,
               `metadata` JSON DEFAULT NULL,
               `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (`id`),
               KEY `memory_id` (`memory_id`),
               KEY `context_type` (`context_type`),
               FOREIGN KEY (`memory_id`) REFERENCES `{$main_table}`(`id`) ON DELETE CASCADE
           ) {$charset_collate};",
           
           'adm_feedback' => "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}adm_feedback` (
               `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
               `memory_id` BIGINT(20) NOT NULL,
               `rating` TINYINT DEFAULT 0,
               `helpful` BOOLEAN DEFAULT NULL,
               `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
               `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (`id`),
               KEY `memory_id` (`memory_id`),
               KEY `user_id` (`user_id`),
               FOREIGN KEY (`memory_id`) REFERENCES `{$main_table}`(`id`) ON DELETE CASCADE
           ) {$charset_collate};",
           
           'adm_arvan_sync' => "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}adm_arvan_sync` (
               `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
               `local_id` BIGINT(20) NOT NULL,
               `remote_id` VARCHAR(255) NOT NULL,
               `sync_status` ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
               `last_sync` DATETIME DEFAULT NULL,
               `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (`id`),
               UNIQUE KEY `local_id` (`local_id`),
               KEY `sync_status` (`sync_status`)
           ) {$charset_collate};"
       ];
       
       foreach ($tables as $table_name => $sql) {
           dbDelta($sql);
       }
       
       // Add version tracking
       update_option('adm_db_version', '9.0.0');
       
       return true;
   }
}
