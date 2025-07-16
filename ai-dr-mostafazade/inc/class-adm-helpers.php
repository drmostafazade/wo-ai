<?php
/**
* Helper Functions and Utilities
*/
class ADM_Helpers {
   
   /**
    * Get available personas
    */
   public static function get_personas() {
       return [
           'general' => 'دستیار عمومی برنامه‌نویسی',
           'bash_scripter' => 'متخصص Bash/Linux',
           'wordpress_dev' => 'توسعه‌دهنده WordPress',
           'frontend_dev' => 'برنامه‌نویس Frontend (JS/CSS)',
           'python_expert' => 'متخصص پایتون و دیتا',
           'odoo_dev' => 'توسعه‌دهنده Odoo',
           'sql_architect' => 'معمار دیتابیس (SQL)',
           'api_expert' => 'متخصص API و وب‌سرویس',
           'custom' => 'شخصیت سفارشی'
       ];
   }
   
   /**
    * Get system prompt for persona
    */
   public static function get_system_prompt($persona, $versions = [], $custom_prompt = '') {
       if ($persona === 'custom') {
           return $custom_prompt;
       }
       
       $version_str = !empty($versions[$persona]) ? "نسخه " . esc_html($versions[$persona]) : '';
       
       $prompts = [
           'bash_scripter' => "شما یک متخصص ارشد اسکریپت‌نویسی Bash و مدیریت سیستم‌های لینوکس هستید. همیشه اسکریپت‌های کامل، بهینه و قابل اجرا را در یک بلوک کد #!/bin/bash ارائه دهید. به مدیریت خطا و کامنت‌گذاری توجه کنید. پاسخ‌ها باید کاملا به زبان فارسی باشد.",
           
           'wordpress_dev' => "شما یک توسعه‌دهنده ارشد وردپرس {$version_str} هستید. با دانش عمیق از هوک‌ها (Actions/Filters)، Post Types، WP_Query، REST API و امنیت (Nonces, Sanitization). کدهای PHP امن و بهینه برای وردپرس ارائه دهید. پاسخ باید کاملا به زبان فارسی باشد.",
           
           'frontend_dev' => "شما یک متخصص ارشد توسعه Frontend مسلط به JavaScript (ES6+), CSS3 و HTML5 هستید. کدهای مدرن، واکنش‌گرا و سازگار با مرورگرهای مختلف ارائه دهید. پاسخ باید کاملا به زبان فارسی باشد.",
           
           'python_expert' => "شما یک متخصص پایتون ۳ و کتابخانه‌های مرتبط مانند Pandas, NumPy و Scikit-learn هستید. کدهای تمیز و اصطلاحی (idiomatic) پایتون برای تحلیل داده و اسکریپت‌نویسی ارائه دهید. پاسخ باید کاملا به زبان فارسی باشد.",
           
           'odoo_dev' => "شما یک توسعه‌دهنده Odoo {$version_str} هستید. با درک کامل از ساختار ماژولار، مدل‌های ORM (models.Model)، ارث‌بری ویوهای XML و متدهای create/write/search، پاسخ‌های تخصصی ارائه دهید. پاسخ باید کاملا به زبان فارسی باشد.",
           
           'sql_architect' => "شما یک معمار دیتابیس و متخصص SQL هستید. با تسلط بر طراحی اسکیمای نرمال‌شده، ایندکس‌گذاری و نوشتن کوئری‌های پیچیده و بهینه برای MySQL و PostgreSQL. پاسخ باید کاملا به زبان فارسی باشد.",
           
           'api_expert' => "شما یک متخصص طراحی و پیاده‌سازی APIهای RESTful و GraphQL هستید. بهترین شیوه‌ها (Best Practices) در زمینه طراحی Endpoint، احرازهویت (Authentication) و مدیریت خطا را در پاسخ‌های خود لحاظ کنید. پاسخ باید کاملا به زبان فارسی باشد.",
           
           'general' => 'شما یک دستیار برنامه‌نویس خبره هستید. همیشه به زبان فارسی پاسخ دهید و کدها را در بلوک مارک‌داون قرار دهید.'
       ];
       
       return $prompts[$persona] ?? $prompts['general'];
   }
   
   /**
    * Format file size
    */
   public static function format_size($bytes) {
       $units = ['B', 'KB', 'MB', 'GB'];
       $i = 0;
       
       while ($bytes >= 1024 && $i < count($units) - 1) {
           $bytes /= 1024;
           $i++;
       }
       
       return round($bytes, 2) . ' ' . $units[$i];
   }
   
   /**
    * Get supported file types for context
    */
   public static function get_supported_file_types() {
       return [
           'text/plain' => ['.txt', '.log', '.md'],
           'text/csv' => ['.csv'],
           'application/json' => ['.json'],
           'text/markdown' => ['.md', '.markdown'],
           'text/x-php' => ['.php'],
           'text/x-python' => ['.py'],
           'text/javascript' => ['.js'],
           'text/css' => ['.css'],
           'text/html' => ['.html', '.htm'],
           'text/xml' => ['.xml'],
           'application/sql' => ['.sql']
       ];
   }
   
   /**
    * Sanitize filename
    */
   public static function sanitize_filename($filename) {
       $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
       return substr($filename, 0, 255);
   }
   
   /**
    * Get memory usage info
    */
   public static function get_memory_info() {
       return [
           'current' => memory_get_usage(true),
           'peak' => memory_get_peak_usage(true),
           'limit' => ini_get('memory_limit')
       ];
   }
   
   /**
    * Log debug information
    */
   public static function log($message, $type = 'info') {
       if (defined('WP_DEBUG') && WP_DEBUG) {
           error_log("[AI Dr. Mostafazade][$type] " . $message);
       }
   }
   
   /**
    * Get plugin asset URL
    */
   public static function asset_url($path) {
       return ADM_PLUGIN_URL . 'assets/' . ltrim($path, '/');
   }
   
   /**
    * Check if request is AJAX
    */
   public static function is_ajax() {
       return defined('DOING_AJAX') && DOING_AJAX;
   }
   
   /**
    * Get current user capability for plugin
    */
   public static function current_user_can_use() {
       return current_user_can(apply_filters('adm_capability', 'manage_options'));
   }
}
