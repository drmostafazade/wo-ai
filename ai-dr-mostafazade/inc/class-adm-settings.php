<?php
/**
* Enhanced Settings Page with ArvanCloud Config
*/
class ADM_Settings {
   
   public function __construct() {
       add_action('admin_menu', [$this, 'add_admin_menu']);
       add_action('admin_init', [$this, 'register_settings']);
       add_action('wp_ajax_adm_test_connection', [$this, 'test_connection']);
       add_action('wp_ajax_adm_update_clusters', [$this, 'update_clusters']);
   }
   
   public function add_admin_menu() {
       add_menu_page(
           'AI Dr. Mostafazade',
           'AI Dr. Mostafazade',
           'manage_options',
           'ai-dr-mostafazade',
           [$this, 'render_page'],
           'dashicons-superhero',
           30
       );
       
       add_submenu_page(
           'ai-dr-mostafazade',
           'تنظیمات',
           'تنظیمات',
           'manage_options',
           'ai-dr-mostafazade',
           [$this, 'render_page']
       );
       
       add_submenu_page(
           'ai-dr-mostafazade',
           'حافظه معنایی',
           'حافظه معنایی',
           'manage_options',
           'adm-memory',
           [$this, 'render_memory_page']
       );
       
       add_submenu_page(
           'ai-dr-mostafazade',
           'افزونه‌ها',
           'افزونه‌ها',
           'manage_options',
           'adm-addons',
           [$this, 'render_addons_page']
       );
   }
   
   public function register_settings() {
       register_setting('adm_options_group', 'adm_options', [$this, 'sanitize_options']);
   }
   
   public function sanitize_options($input) {
       // Encrypt sensitive data
       if (!empty($input['arvan_config']['database']['user'])) {
           $arvan = new ADM_ArvanCloud();
           $input['arvan_config']['database']['user'] = $arvan->encrypt($input['arvan_config']['database']['user']);
           $input['arvan_config']['database']['pass'] = $arvan->encrypt($input['arvan_config']['database']['pass']);
           
           if (!empty($input['arvan_config']['redis']['auth'])) {
               $input['arvan_config']['redis']['auth'] = $arvan->encrypt($input['arvan_config']['redis']['auth']);
           }
       }
       
       return $input;
   }
   
   public function render_page() {
       $opts = get_option('adm_options', []);
       $models = ADM_Claude_API::get_available_models();
       $personas = ADM_Helpers::get_personas();
       ?>
       <div class="wrap adm-admin-wrap">
           <h1>
               <span class="dashicons dashicons-superhero"></span>
               AI Dr. Mostafazade - تنظیمات
           </h1>
           
           <div class="adm-admin-grid">
               <div class="adm-main-settings">
                   <form method="post" action="options.php" id="adm-settings-form">
                       <?php settings_fields('adm_options_group'); ?>
                       
                       <!-- API Settings -->
                       <div class="adm-settings-section">
                           <h2>تنظیمات API</h2>
                           <table class="form-table">
                               <tr>
                                   <th><label for="api_key">کلید API کلاد</label></th>
                                   <td>
                                       <input type="password" id="api_key" name="adm_options[api_key]" 
                                              value="<?php echo esc_attr($opts['api_key'] ?? ''); ?>" 
                                              class="regular-text" />
                                       <p class="description">
                                           کلید API خود را از 
                                           <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a>
                                           دریافت کنید.
                                       </p>
                                   </td>
                               </tr>
                               
                               <tr>
                                   <th><label for="openai_api_key">کلید API OpenAI</label></th>
                                   <td>
                                       <input type="password" id="openai_api_key" name="adm_options[openai_api_key]" 
                                              value="<?php echo esc_attr($opts['openai_api_key'] ?? ''); ?>" 
                                              class="regular-text" />
                                       <p class="description">
                                           برای استفاده از Embeddings - از 
                                           <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                                       </p>
                                   </td>
                               </tr>
                               
                               <tr>
                                   <th><label for="model">مدل Claude</label></th>
                                   <td>
                                       <select id="model" name="adm_options[model]">
                                           <?php foreach ($models as $model): ?>
                                           <option value="<?php echo esc_attr($model['id']); ?>" 
                                                   <?php selected($opts['model'] ?? '', $model['id']); ?>>
                                               <?php echo esc_html($model['name']); ?>
                                           </option>
                                           <?php endforeach; ?>
                                       </select>
                                   </td>
                               </tr>
                           </table>
                       </div>
                       
                       <!-- Persona Settings -->
                       <div class="adm-settings-section">
                           <h2>موتور شخصیت‌سازی</h2>
                           <table class="form-table">
                               <tr>
                                   <th><label for="persona-select">شخصیت تخصصی</label></th>
                                   <td>
                                       <select id="persona-select" name="adm_options[persona]">
                                           <?php foreach ($personas as $key => $label): ?>
                                           <option value="<?php echo $key; ?>" 
                                                   <?php selected($opts['persona'] ?? 'general', $key); ?>>
                                               <?php echo $label; ?>
                                           </option>
                                           <?php endforeach; ?>
                                       </select>
                                   </td>
                               </tr>
                               
                               <!-- Dynamic version inputs -->
                               <tr class="version-input-row" data-persona-for="wordpress_dev">
                                   <th><label>ورژن وردپرس</label></th>
                                   <td>
                                       <input type="text" name="adm_options[persona_versions][wordpress_dev]" 
                                              value="<?php echo esc_attr($opts['persona_versions']['wordpress_dev'] ?? '6.5'); ?>" />
                                   </td>
                               </tr>
                               
                               <tr class="version-input-row" data-persona-for="odoo_dev">
                                   <th><label>ورژن Odoo</label></th>
                                   <td>
                                       <input type="text" name="adm_options[persona_versions][odoo_dev]" 
                                              value="<?php echo esc_attr($opts['persona_versions']['odoo_dev'] ?? '17.0'); ?>" />
                                   </td>
                               </tr>
                               
                               <tr id="custom-persona-row">
                                   <th><label for="custom_prompt">دستورالعمل سفارشی</label></th>
                                   <td>
                                       <textarea name="adm_options[custom_prompt]" rows="8" class="large-text">
                                           <?php echo esc_textarea($opts['custom_prompt'] ?? ''); ?>
                                       </textarea>
                                   </td>
                               </tr>
                           </table>
                       </div>
                       
                       <!-- Memory Settings -->
                       <div class="adm-settings-section">
                           <h2>حافظه معنایی</h2>
                           <table class="form-table">
                               <tr>
                                   <th><label for="enable_memory">فعال‌سازی حافظه</label></th>
                                   <td>
                                       <label>
                                           <input type="checkbox" id="enable_memory" 
                                                  name="adm_options[enable_memory]" value="1" 
                                                  <?php checked($opts['enable_memory'] ?? 0, 1); ?> />
                                           ذخیره مکالمات برای یادگیری
                                       </label>
                                   </td>
                               </tr>
                               
                               <tr>
                                   <th><label for="enable_embeddings">Vector Embeddings</label></th>
                                   <td>
                                       <label>
                                           <input type="checkbox" id="enable_embeddings" 
                                                  name="adm_options[enable_embeddings]" value="1" 
                                                  <?php checked($opts['enable_embeddings'] ?? 0, 1); ?> />
                                           استفاده از OpenAI Embeddings برای جستجوی معنایی
                                       </label>
                                   </td>
                               </tr>
                               
                               <tr>
                                   <th>مدیریت Clusters</th>
                                   <td>
                                       <button type="button" id="update-clusters-btn" class="button">
                                           بروزرسانی Clusters
                                       </button>
                                       <span id="cluster-status"></span>
                                   </td>
                               </tr>
                           </table>
                       </div>
                       
                       <!-- ArvanCloud Settings -->
                       <div class="adm-settings-section">
                           <h2>تنظیمات ArvanCloud</h2>
                           <table class="form-table">
                               <tr>
                                   <th><label for="enable_arvan">فعال‌سازی ArvanCloud</label></th>
                                   <td>
                                       <label>
                                           <input type="checkbox" id="enable_arvan" 
                                                  name="adm_options[enable_arvan]" value="1" 
                                                  <?php checked($opts['enable_arvan'] ?? 0, 1); ?> />
                                           اتصال به دیتابیس ابری ArvanCloud
                                       </label>
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th><label>API Key</label></th>
                                   <td>
                                       <input type="password" name="adm_options[arvan_config][api_key]" 
                                              value="<?php echo esc_attr($opts['arvan_config']['api_key'] ?? ''); ?>" 
                                              class="regular-text" />
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th><label>نوع دیتابیس</label></th>
                                   <td>
                                       <select name="adm_options[arvan_config][database][type]">
                                           <option value="mysql" <?php selected($opts['arvan_config']['database']['type'] ?? '', 'mysql'); ?>>
                                               MySQL
                                           </option>
                                           <option value="pgsql" <?php selected($opts['arvan_config']['database']['type'] ?? '', 'pgsql'); ?>>
                                               PostgreSQL
                                           </option>
                                       </select>
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th><label>Host</label></th>
                                   <td>
                                       <input type="text" name="adm_options[arvan_config][database][host]" 
                                              value="<?php echo esc_attr($opts['arvan_config']['database']['host'] ?? ''); ?>" 
                                              class="regular-text" 
                                              placeholder="db-instance.iran.arvancloud.ir" />
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th><label>Port</label></th>
                                   <td>
                                       <input type="number" name="adm_options[arvan_config][database][port]" 
                                              value="<?php echo esc_attr($opts['arvan_config']['database']['port'] ?? '3306'); ?>" 
                                              class="small-text" />
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th><label>Database Name</label></th>
                                   <td>
                                       <input type="text" name="adm_options[arvan_config][database][name]" 
                                              value="<?php echo esc_attr($opts['arvan_config']['database']['name'] ?? ''); ?>" 
                                              class="regular-text" />
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th><label>Username</label></th>
                                   <td>
                                       <input type="text" name="adm_options[arvan_config][database][user]" 
                                              value="" 
                                              class="regular-text" 
                                              placeholder="رمزگذاری شده ذخیره می‌شود" />
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th><label>Password</label></th>
                                   <td>
                                       <input type="password" name="adm_options[arvan_config][database][pass]" 
                                              value="" 
                                              class="regular-text" 
                                              placeholder="رمزگذاری شده ذخیره می‌شود" />
                                   </td>
                               </tr>
                               
                               <tr class="arvan-config-row">
                                   <th>تست اتصال</th>
                                   <td>
                                       <button type="button" id="test-arvan-btn" class="button">
                                           تست اتصال
                                       </button>
                                       <span id="arvan-test-status"></span>
                                   </td>
                               </tr>
                           </table>
                       </div>
                       
                       <?php submit_button('ذخیره تنظیمات'); ?>
                   </form>
               </div>
               
               <div class="adm-sidebar">
                   <!-- Quick Stats -->
                   <div class="adm-widget">
                       <h3>آمار سریع</h3>
                       <?php $this->render_quick_stats(); ?>
                   </div>
                   
                   <!-- System Info -->
                   <div class="adm-widget">
                       <h3>اطلاعات سیستم</h3>
                       <?php $this->render_system_info(); ?>
                   </div>
                   
                   <!-- Quick Actions -->
                   <div class="adm-widget">
                       <h3>دسترسی سریع</h3>
                       <a href="<?php echo admin_url('admin.php?page=adm-memory'); ?>" class="button button-secondary">
                           مدیریت حافظه
                       </a>
                       <a href="<?php echo admin_url('admin.php?page=adm-addons'); ?>" class="button button-secondary">
                           مدیریت افزونه‌ها
                       </a>
                   </div>
               </div>
           </div>
       </div>
       <?php
   }
   
   private function render_quick_stats() {
       global $wpdb;
       
       $memories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_memory");
       $embeddings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings");
       $clusters = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_clusters");
       
       echo "<ul>";
       echo "<li>تعداد خاطرات: <strong>{$memories}</strong></li>";
       echo "<li>تعداد Embeddings: <strong>{$embeddings}</strong></li>";
       echo "<li>تعداد Clusters: <strong>{$clusters}</strong></li>";
       echo "</ul>";
   }
   
   private function render_system_info() {
       echo "<ul>";
       echo "<li>PHP Version: <strong>" . PHP_VERSION . "</strong></li>";
       echo "<li>WordPress: <strong>" . get_bloginfo('version') . "</strong></li>";
       echo "<li>Plugin Version: <strong>" . ADM_VERSION . "</strong></li>";
       echo "<li>Memory Limit: <strong>" . ini_get('memory_limit') . "</strong></li>";
       echo "</ul>";
   }
   
   public function render_memory_page() {
       ?>
       <div class="wrap">
           <h1>مدیریت حافظه معنایی</h1>
           <!-- Memory management interface here -->
       </div>
       <?php
   }
   
   public function render_addons_page() {
       $addon_loader = new ADM_Addon_Loader();
       $addons = $addon_loader->get_available_addons();
       ?>
       <div class="wrap">
           <h1>مدیریت افزونه‌ها</h1>
           
           <div class="adm-addons-grid">
               <?php foreach ($addons as $addon): ?>
               <div class="adm-addon-card">
                   <h3><?php echo esc_html($addon['name']); ?></h3>
                   <p><?php echo esc_html($addon['description']); ?></p>
                   <div class="addon-meta">
                       <span>نسخه: <?php echo esc_html($addon['version']); ?></span>
                       <span>نویسنده: <?php echo esc_html($addon['author']); ?></span>
                   </div>
                   <?php if ($addon['active']): ?>
                       <button class="button" disabled>فعال</button>
                   <?php else: ?>
                       <button class="button button-primary">فعال‌سازی</button>
                   <?php endif; ?>
               </div>
               <?php endforeach; ?>
           </div>
       </div>
       <?php
   }
   
   public function test_connection() {
       check_ajax_referer('adm-admin-nonce', 'nonce');
       
       $arvan = new ADM_ArvanCloud();
       
       if ($arvan->connect_database()) {
           wp_send_json_success(['message' => 'اتصال موفقیت‌آمیز بود!']);
       } else {
           wp_send_json_error(['message' => 'اتصال ناموفق. لطفاً تنظیمات را بررسی کنید.']);
       }
   }
   
   public function update_clusters() {
       check_ajax_referer('adm-admin-nonce', 'nonce');
       
       $vector_db = new ADM_Vector_DB();
       
       if ($vector_db->update_clusters()) {
           wp_send_json_success(['message' => 'Clusters با موفقیت بروزرسانی شدند.']);
       } else {
           wp_send_json_error(['message' => 'خطا در بروزرسانی. حداقل 10 embedding نیاز است.']);
       }
   }
}
