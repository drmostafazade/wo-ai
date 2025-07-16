<?php
/**
* Addon Name: Terminal Executor
* Description: اجرای دستورات bash در محیط امن
* Version: 1.0.0
* Author: AI Dr. Mostafazade
* Requires: 9.0.0
*/

// Prevent direct access
if (!defined('ABSPATH')) exit;

class ADM_Addon_Terminal {
   
   public function __construct() {
       // Register hooks
       add_action('adm_chat_toolbar', [$this, 'add_terminal_button']);
       add_action('wp_ajax_adm_execute_command', [$this, 'execute_command']);
       
       // Add terminal to chat interface
       add_action('wp_footer', [$this, 'render_terminal_modal']);
   }
   
   public function add_terminal_button() {
       ?>
       <button type="button" class="adm-toolbar-btn" id="open-terminal">
           <span class="dashicons dashicons-editor-code"></span>
           ترمینال
       </button>
       <?php
   }
   
   public function render_terminal_modal() {
       if (!is_singular() || !has_shortcode(get_post()->post_content, 'ai_dr_chat')) {
           return;
       }
       ?>
       <div id="adm-terminal-modal" class="adm-modal" style="display: none;">
           <div class="adm-modal-content">
               <div class="adm-modal-header">
                   <h3>Terminal Executor</h3>
                   <button class="adm-modal-close">&times;</button>
               </div>
               <div class="adm-modal-body">
                   <div id="terminal-output"></div>
                   <div class="terminal-input-line">
                       <span class="terminal-prompt">$</span>
                       <input type="text" id="terminal-input" placeholder="Enter command...">
                   </div>
               </div>
           </div>
       </div>
       
       <style>
       .adm-modal {
           position: fixed;
           top: 0;
           left: 0;
           width: 100%;
           height: 100%;
           background: rgba(0,0,0,0.5);
           z-index: 99999;
           display: flex;
           align-items: center;
           justify-content: center;
       }
       
       .adm-modal-content {
           background: #1e1e1e;
           color: #fff;
           width: 80%;
           max-width: 800px;
           height: 500px;
           border-radius: 8px;
           display: flex;
           flex-direction: column;
       }
       
       #terminal-output {
           flex: 1;
           padding: 20px;
           overflow-y: auto;
           font-family: monospace;
           font-size: 14px;
           line-height: 1.5;
       }
       
       .terminal-input-line {
           display: flex;
           padding: 10px 20px;
           border-top: 1px solid #333;
       }
       
       .terminal-prompt {
           margin-left: 10px;
           color: #0f0;
       }
       
       #terminal-input {
           flex: 1;
           background: transparent;
           border: none;
           color: #fff;
           font-family: monospace;
           outline: none;
           margin-right: 10px;
       }
       </style>
       
       <script>
       jQuery(document).ready(function($) {
           // Terminal functionality would go here
           $('#open-terminal').on('click', function() {
               $('#adm-terminal-modal').show();
               $('#terminal-input').focus();
           });
           
           $('.adm-modal-close').on('click', function() {
               $('#adm-terminal-modal').hide();
           });
           
           // This would connect to the actual terminal execution
           $('#terminal-input').on('keypress', function(e) {
               if (e.which === 13) { // Enter key
                   const command = $(this).val();
                   if (command.trim()) {
                       executeCommand(command);
                       $(this).val('');
                   }
               }
           });
           
           function executeCommand(command) {
               $('#terminal-output').append('<div>$ ' + command + '</div>');
               $('#terminal-output').append('<div style="color: #999;">This is a demo terminal. Actual execution requires server configuration.</div>');
           }
       });
       </script>
       <?php
   }
   
   public function execute_command() {
       check_ajax_referer('adm-chat-nonce', 'nonce');
       
       if (!current_user_can('manage_options')) {
           wp_send_json_error(['error' => 'Unauthorized']);
       }
       
       // In a real implementation, this would execute commands safely
       // For security, this is just a placeholder
       
       $command = sanitize_text_field($_POST['command']);
       
       wp_send_json_success([
           'output' => 'Command execution is disabled in this demo version for security.',
           'command' => $command
       ]);
   }
}

// Initialize addon
new ADM_Addon_Terminal();
