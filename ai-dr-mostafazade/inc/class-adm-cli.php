<?php
/**
* WP-CLI Commands
*/

if (!defined('WP_CLI') || !WP_CLI) {
   return;
}

class ADM_CLI_Commands {
   
   /**
    * Manage AI Dr. Mostafazade plugin
    */
   public function __construct() {
       WP_CLI::add_command('ai-dr', $this);
   }
   
   /**
    * Get plugin status
    * 
    * ## EXAMPLES
    * 
    *     wp ai-dr status
    */
   public function status($args, $assoc_args) {
       global $wpdb;
       
       $memories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_memory");
       $embeddings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings");
       $clusters = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_clusters");
       
       WP_CLI::line('AI Dr. Mostafazade Status:');
       WP_CLI::line('------------------------');
       WP_CLI::line("Memories: $memories");
       WP_CLI::line("Embeddings: $embeddings");
       WP_CLI::line("Clusters: $clusters");
       
       $options = get_option('adm_options', []);
       $api_status = !empty($options['api_key']) ? 'Configured' : 'Not configured';
       $openai_status = !empty($options['openai_api_key']) ? 'Configured' : 'Not configured';
       
       WP_CLI::line("\nAPI Status:");
       WP_CLI::line("Claude API: $api_status");
       WP_CLI::line("OpenAI API: $openai_status");
       
       WP_CLI::success('Status check complete');
   }
   
   /**
    * Clear memory
    * 
    * ## OPTIONS
    * 
    * [--type=<type>]
    * : Type of memory to clear (all, local, embeddings)
    * default: all
    * 
    * ## EXAMPLES
    * 
    *     wp ai-dr clear-memory
    *     wp ai-dr clear-memory --type=embeddings
    */
   public function clear_memory($args, $assoc_args) {
       $type = $assoc_args['type'] ?? 'all';
       
       WP_CLI::confirm("Are you sure you want to clear $type memory?");
       
       global $wpdb;
       
       switch ($type) {
           case 'local':
               $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}adm_memory");
               WP_CLI::success('Local memory cleared');
               break;
               
           case 'embeddings':
               $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}adm_embeddings");
               $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}adm_clusters");
               WP_CLI::success('Embeddings and clusters cleared');
               break;
               
           case 'all':
               $tables = ['adm_memory', 'adm_embeddings', 'adm_clusters', 'adm_contexts', 'adm_feedback'];
               foreach ($tables as $table) {
                   $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$table}");
               }
               WP_CLI::success('All memory cleared');
               break;
               
           default:
               WP_CLI::error("Invalid type: $type");
       }
   }
   
   /**
    * Update clusters
    * 
    * ## OPTIONS
    * 
    * [--clusters=<number>]
    * : Number of clusters to create
    * default: 10
    * 
    * ## EXAMPLES
    * 
    *     wp ai-dr update-clusters
    *     wp ai-dr update-clusters --clusters=20
    */
   public function update_clusters($args, $assoc_args) {
       $num_clusters = $assoc_args['clusters'] ?? 10;
       
       WP_CLI::line("Updating clusters to $num_clusters...");
       
       $vector_db = new ADM_Vector_DB();
       
       if ($vector_db->update_clusters($num_clusters)) {
           WP_CLI::success('Clusters updated successfully');
       } else {
           WP_CLI::error('Failed to update clusters. Make sure you have enough embeddings.');
       }
   }
   
   /**
    * Sync with ArvanCloud
    * 
    * ## OPTIONS
    * 
    * [--limit=<number>]
    * : Number of records to sync
    * default: 100
    * 
    * ## EXAMPLES
    * 
    *     wp ai-dr sync-arvan
    *     wp ai-dr sync-arvan --limit=50
    */
   public function sync_arvan($args, $assoc_args) {
       $limit = $assoc_args['limit'] ?? 100;
       
       $options = get_option('adm_options', []);
       
       if (empty($options['enable_arvan'])) {
           WP_CLI::error('ArvanCloud is not enabled in settings');
       }
       
       global $wpdb;
       
       $unsynced = $wpdb->get_results($wpdb->prepare(
           "SELECT m.* FROM {$wpdb->prefix}adm_memory m
            LEFT JOIN {$wpdb->prefix}adm_arvan_sync s ON m.id = s.local_id
            WHERE s.id IS NULL OR s.sync_status = 'failed'
            LIMIT %d",
           $limit
       ));
       
       if (empty($unsynced)) {
           WP_CLI::line('No unsynced memories found');
           return;
       }
       
       $arvan = new ADM_ArvanCloud();
       $progress = WP_CLI\Utils\make_progress_bar('Syncing memories', count($unsynced));
       $synced = 0;
       
       foreach ($unsynced as $memory) {
           $data = [
               'user_query' => $memory->user_query,
               'ai_response' => $memory->ai_response,
               'created_at' => $memory->created_at
           ];
           
           if ($arvan->sync_memory($memory->id, $data)) {
               $synced++;
           }
           
           $progress->tick();
       }
       
       $progress->finish();
       WP_CLI::success("Synced $synced memories to ArvanCloud");
   }
   
   /**
    * Generate embeddings for existing memories
    * 
    * ## OPTIONS
    * 
    * [--limit=<number>]
    * : Number of memories to process
    * default: 100
    * 
    * ## EXAMPLES
    * 
    *     wp ai-dr generate-embeddings
    *     wp ai-dr generate-embeddings --limit=50
    */
   public function generate_embeddings($args, $assoc_args) {
       $limit = $assoc_args['limit'] ?? 100;
       
       $options = get_option('adm_options', []);
       
       if (empty($options['openai_api_key'])) {
           WP_CLI::error('OpenAI API key is not configured');
       }
       
       global $wpdb;
       
       // Get memories without embeddings
       $memories = $wpdb->get_results($wpdb->prepare(
           "SELECT m.* FROM {$wpdb->prefix}adm_memory m
            LEFT JOIN {$wpdb->prefix}adm_embeddings e ON m.id = e.memory_id
            WHERE e.id IS NULL
            LIMIT %d",
           $limit
       ));
       
       if (empty($memories)) {
           WP_CLI::line('All memories already have embeddings');
           return;
       }
       
       $vector_db = new ADM_Vector_DB();
       $progress = WP_CLI\Utils\make_progress_bar('Generating embeddings', count($memories));
       $generated = 0;
       
       foreach ($memories as $memory) {
           $text = $memory->user_query . ' ' . substr($memory->ai_response, 0, 1000);
           $embedding = $vector_db->generate_embedding($text);
           
           if ($embedding) {
               $vector_db->save_embedding($memory->id, $embedding);
               $generated++;
           }
           
           $progress->tick();
           
           // Rate limiting
           usleep(100000); // 0.1 second delay
       }
       
       $progress->finish();
       WP_CLI::success("Generated $generated embeddings");
   }
   
   /**
    * Test Claude API connection
    * 
    * ## EXAMPLES
    * 
    *     wp ai-dr test-api
    */
   public function test_api($args, $assoc_args) {
       $api = new ADM_Claude_API();
       
       WP_CLI::line('Testing Claude API connection...');
       
       $response = $api->send_message([
           ['role' => 'user', 'content' => [['type' => 'text', 'text' => 'Hello, Claude!']]]
       ], ['max_tokens' => 10]);
       
       if ($response['success']) {
           WP_CLI::success('API connection successful!');
           WP_CLI::line('Response: ' . $response['data']['content'][0]['text']);
       } else {
           WP_CLI::error('API connection failed: ' . $response['error']);
       }
   }
   
   /**
    * Export memory to JSON
    * 
    * ## OPTIONS
    * 
    * [--output=<file>]
    * : Output file path
    * default: ai-dr-memory-export.json
    * 
    * ## EXAMPLES
    * 
    *     wp ai-dr export
    *     wp ai-dr export --output=/tmp/memory.json
    */
   public function export($args, $assoc_args) {
       $output = $assoc_args['output'] ?? 'ai-dr-memory-export.json';
       
       global $wpdb;
       
       $memories = $wpdb->get_results(
           "SELECT * FROM {$wpdb->prefix}adm_memory ORDER BY created_at DESC"
       );
       
       $export_data = [
           'version' => ADM_VERSION,
           'exported_at' => current_time('mysql'),
           'total_memories' => count($memories),
           'memories' => $memories
       ];
       
       file_put_contents($output, json_encode($export_data, JSON_PRETTY_PRINT));
       
       WP_CLI::success("Exported " . count($memories) . " memories to $output");
   }
}

// Register CLI commands
new ADM_CLI_Commands();
