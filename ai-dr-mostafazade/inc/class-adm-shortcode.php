<?php
/**
* Enhanced Shortcode with Full-Screen Chat Interface
*/
class ADM_Shortcode {
   
   public function __construct() {
       add_shortcode('ai_dr_chat', [$this, 'render_chat']);
       add_shortcode('claude_pro_hub', [$this, 'render_chat']); // Backward compatibility
   }
   
   public function render_chat($atts = []) {
       $atts = shortcode_atts([
           'height' => '100vh',
           'theme' => 'dark',
           'tabs' => 'true',
           'context' => 'true'
       ], $atts);
       
       ob_start();
       ?>
       <div id="adm-chat-container" class="adm-chat-<?php echo esc_attr($atts['theme']); ?>" 
            style="height: <?php echo esc_attr($atts['height']); ?>;">
           
           <?php if ($atts['tabs'] === 'true'): ?>
           <div class="adm-chat-tabs">
               <div class="adm-tab active" data-tab="main">گفتگوی اصلی</div>
               <button class="adm-new-tab">+ تب جدید</button>
           </div>
           <?php endif; ?>
           
           <div class="adm-chat-main">
               <div class="adm-chat-sidebar">
                   <?php if ($atts['context'] === 'true'): ?>
                   <div class="adm-context-panel">
                       <h3>Context Files</h3>
                       <div class="adm-context-dropzone">
                           <p>فایل‌ها را اینجا رها کنید</p>
                           <input type="file" id="adm-context-upload" multiple accept=".txt,.md,.json,.csv">
                       </div>
                       <div class="adm-context-files"></div>
                   </div>
                   <?php endif; ?>
                   
                   <div class="adm-memory-stats">
                       <h3>وضعیت حافظه</h3>
                       <div class="adm-stats-content">
                           <div class="stat-item">
                               <span class="stat-label">کل خاطرات:</span>
                               <span class="stat-value" id="total-memories">0</span>
                           </div>
                           <div class="stat-item">
                               <span class="stat-label">Embeddings:</span>
                               <span class="stat-value" id="total-embeddings">0</span>
                           </div>
                           <div class="stat-item">
                               <span class="stat-label">Clusters:</span>
                               <span class="stat-value" id="total-clusters">0</span>
                           </div>
                       </div>
                   </div>
               </div>
               
               <div class="adm-chat-content">
                   <div id="adm-chat-messages" class="adm-messages-container">
                       <div class="adm-message adm-message-bot">
                           <div class="adm-message-header">
                               <span class="adm-avatar">🤖</span>
                               <span class="adm-name">AI Dr. Mostafazade</span>
                           </div>
                           <div class="adm-message-content">
                               سلام! من دستیار هوشمند شما با حافظه معنایی پیشرفته هستم. چطور می‌توانم کمکتان کنم؟
                           </div>
                       </div>
                   </div>
                   
                   <div class="adm-input-area">
                       <form id="adm-chat-form">
                           <div class="adm-input-wrapper">
                               <textarea id="adm-user-input" 
                                         placeholder="سوال یا درخواست خود را بنویسید..." 
                                         rows="3"></textarea>
                               <div class="adm-input-actions">
                                   <button type="button" class="adm-voice-btn" title="ضبط صدا">🎤</button>
                                   <button type="submit" class="adm-send-btn">ارسال</button>
                               </div>
                           </div>
                       </form>
                       
                       <div class="adm-input-options">
                           <label>
                               <input type="checkbox" id="adm-format-script"> 
                               خروجی به صورت اسکریپت
                           </label>
                           <label>
                               <input type="checkbox" id="adm-use-embeddings" checked> 
                               استفاده از حافظه معنایی
                           </label>
                           <span class="adm-status" id="adm-status-text"></span>
                       </div>
                   </div>
               </div>
           </div>
       </div>
       <?php
       return ob_get_clean();
   }
}
