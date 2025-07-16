<?php
/**
 * Template Name: AI Chat Full Width
 * Description: Full width chat interface without header/footer
 */

// Remove admin bar
add_filter('show_admin_bar', '__return_false');

// Start output
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden;
        }
        #wpadminbar,
        header,
        .site-header,
        footer,
        .site-footer,
        aside,
        .sidebar,
        .widget-area {
            display: none !important;
        }
        #adm-chat-container {
            width: 100vw !important;
            height: 100vh !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .adm-chat-content {
            width: 100% !important;
        }
    </style>
</head>
<body class="adm-fullscreen-chat">
    <?php
    // Only show the chat
    echo do_shortcode('[ai_dr_chat height="100vh" theme="dark" tabs="true" context="true"]');
    ?>
    <?php wp_footer(); ?>
</body>
</html>
