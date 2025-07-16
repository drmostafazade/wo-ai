<?php
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

// Activate terminal addon
$active_addons = get_option('adm_active_addons', []);
if (!in_array('terminal', $active_addons)) {
    $active_addons[] = 'terminal';
    update_option('adm_active_addons', $active_addons);
    echo "Terminal addon activated!";
} else {
    echo "Terminal addon is already active!";
}

// Show all addons
echo "<h3>Active Addons:</h3>";
echo "<pre>" . print_r($active_addons, true) . "</pre>";
