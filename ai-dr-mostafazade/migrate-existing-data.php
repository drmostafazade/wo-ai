<?php
/**
 * Migrate existing CPH data to ADM
 * Run this ONCE after activating the new plugin
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

global $wpdb;

echo "<h1>Migrating existing data from CPH to ADM...</h1>";

// Check if old tables exist
$old_tables = [
    'cph_memory',
    'cph_embeddings', 
    'cph_clusters',
    'cph_contexts',
    'cph_feedback',
    'cph_arvan_sync'
];

$tables_to_migrate = [];
foreach ($old_tables as $table) {
    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'")) {
        $tables_to_migrate[] = $table;
    }
}

if (empty($tables_to_migrate)) {
    echo "<p>No old tables found. Nothing to migrate.</p>";
} else {
    echo "<h2>Found tables to migrate:</h2><ul>";
    foreach ($tables_to_migrate as $table) {
        echo "<li>{$wpdb->prefix}{$table}</li>";
        
        // Copy data to new table
        $new_table = str_replace('cph_', 'adm_', $table);
        
        // First check if new table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$new_table}'")) {
            // Copy data
            $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}{$new_table} SELECT * FROM {$wpdb->prefix}{$table}");
            $count = $wpdb->rows_affected;
            echo " - Migrated {$count} rows to {$wpdb->prefix}{$new_table}<br>";
        }
    }
    echo "</ul>";
}

// Migrate options
$old_options = [
    'cph_options' => 'adm_options',
    'cph_db_version' => 'adm_db_version',
    'cph_active_addons' => 'adm_active_addons'
];

echo "<h2>Migrating options:</h2><ul>";
foreach ($old_options as $old => $new) {
    $value = get_option($old);
    if ($value !== false) {
        update_option($new, $value);
        echo "<li>Migrated {$old} to {$new}</li>";
    }
}
echo "</ul>";

echo "<h2>Migration complete!</h2>";
echo '<p><a href="' . admin_url('admin.php?page=ai-dr-mostafazade') . '">Go to plugin settings</a></p>';
