// Claude Chat Admin JavaScript
(function($) {
    'use strict';
    
    // بررسی jQuery
    if (typeof $ === 'undefined') {
        console.error('jQuery is not available!');
        return;
    }
    
    console.log('Claude Chat Admin JS initialized with jQuery:', $.fn.jquery);
    
    $(document).ready(function() {
        console.log('Document ready - Claude Chat Admin');
        
        // بررسی وجود claudeChat object
        if (typeof claudeChat === 'undefined') {
            console.error('claudeChat object not found!');
            window.claudeChat = {
                ajaxUrl: ajaxurl || '/wp-admin/admin-ajax.php',
                nonce: $('#_wpnonce').val() || ''
            };
        }
        
        // تست اتصال
        $(document).on('click', '#test-connection', function(e) {
            e.preventDefault();
            console.log('Test connection clicked');
            
            var $btn = $(this);
            var $result = $('#test-result');
            
            $btn.prop('disabled', true);
            $result.html('<div class="notice notice-info"><p>در حال تست اتصال...</p></div>');
            
            $.post(claudeChat.ajaxUrl, {
                action: 'claude_test_connection',
                nonce: claudeChat.nonce
            })
            .done(function(response) {
                console.log('Test response:', response);
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>✅ اتصال موفق بود!</p></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>❌ ' + (response.error || 'خطا در اتصال') + '</p></div>');
                }
            })
            .fail(function(jqXHR, textStatus) {
                console.error('Test failed:', textStatus);
                $result.html('<div class="notice notice-error"><p>خطا: ' + textStatus + '</p></div>');
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        });
        
        // بررسی وضعیت API
        $(document).on('click', '#check-api-status', function(e) {
            e.preventDefault();
            console.log('Check API status clicked');
            
            var $btn = $(this);
            var $info = $('#api-status-info');
            
            $btn.prop('disabled', true);
            $info.html('<p>در حال بررسی وضعیت...</p>');
            
            $.post(claudeChat.ajaxUrl, {
                action: 'claude_check_api_status',
                nonce: claudeChat.nonce
            })
            .done(function(response) {
                console.log('Status response:', response);
                if (response.success && response.status) {
                    var html = '<div class="api-status-grid">';
                    html += '<div class="status-item"><strong>وضعیت:</strong> ';
                    html += response.status.api_operational ? '✅ فعال' : '❌ غیرفعال';
                    html += '</div>';
                    if (response.status.response_time) {
                        html += '<div class="status-item"><strong>زمان پاسخ:</strong> ' + response.status.response_time + '</div>';
                    }
                    html += '</div>';
                    $info.html(html);
                } else {
                    $info.html('<p style="color:red;">خطا در دریافت وضعیت</p>');
                }
            })
            .fail(function(jqXHR, textStatus) {
                console.error('Status check failed:', textStatus);
                $info.html('<p style="color:red;">خطا: ' + textStatus + '</p>');
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        });
        
        // بروزرسانی مدل‌ها
        $(document).on('click', '#refresh-models', function(e) {
            e.preventDefault();
            console.log('Refresh models clicked');
            
            var $btn = $(this);
            $btn.prop('disabled', true);
            
            $.post(claudeChat.ajaxUrl, {
                action: 'claude_refresh_models',
                nonce: claudeChat.nonce
            })
            .done(function(response) {
                console.log('Refresh response:', response);
                if (response.success) {
                    alert('لیست مدل‌ها بروزرسانی شد');
                    location.reload();
                } else {
                    alert('خطا در بروزرسانی: ' + (response.error || 'نامشخص'));
                }
            })
            .fail(function(jqXHR, textStatus) {
                console.error('Refresh failed:', textStatus);
                alert('خطا: ' + textStatus);
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        });
        
        // Log button status
        console.log('Buttons found:', {
            test: $('#test-connection').length,
            status: $('#check-api-status').length,
            refresh: $('#refresh-models').length
        });
    });
    
})(jQuery);
