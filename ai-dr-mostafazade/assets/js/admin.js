/**
* AI Dr. Mostafazade - Admin Scripts
*/
jQuery(document).ready(function($) {
   'use strict';
   
   // Elements
   const $personaSelect = $('#persona-select');
   const $customPersonaRow = $('#custom-persona-row');
   const $versionRows = $('.version-input-row');
   const $customPrompt = $('textarea[name="adm_options[custom_prompt]"]');
   const $enableArvan = $('#enable_arvan');
   const $arvanRows = $('.arvan-config-row');
   
   // Persona definitions
   const personas = {
       'bash_scripter': 'شما یک متخصص ارشد اسکریپت‌نویسی Bash و مدیریت سیستم‌های لینوکس هستید...',
       'wordpress_dev': 'شما یک توسعه‌دهنده ارشد وردپرس {version} هستید...',
       'frontend_dev': 'شما یک متخصص ارشد توسعه Frontend مسلط به JavaScript (ES6+), CSS3 و HTML5 هستید...',
       'python_expert': 'شما یک متخصص پایتون ۳ و کتابخانه‌های مرتبط مانند Pandas, NumPy و Scikit-learn هستید...',
       'odoo_dev': 'شما یک توسعه‌دهنده Odoo {version} هستید...',
       'sql_architect': 'شما یک معمار دیتابیس و متخصص SQL هستید...',
       'api_expert': 'شما یک متخصص طراحی و پیاده‌سازی APIهای RESTful و GraphQL هستید...',
       'general': 'شما یک دستیار برنامه‌نویس خبره هستید. همیشه به زبان فارسی پاسخ دهید...'
   };
   
   // Initialize
   init();
   
   function init() {
       // Handle persona selection
       handlePersonaChange();
       $personaSelect.on('change', handlePersonaChange);
       
       // Handle ArvanCloud toggle
       handleArvanToggle();
       $enableArvan.on('change', handleArvanToggle);
       
       // Clear memory button
       $('#clear-memory-btn').on('click', handleClearMemory);
       
       // Test ArvanCloud connection
       $('#test-arvan-btn').on('click', testArvanConnection);
       
       // Update clusters button
       $('#update-clusters-btn').on('click', updateClusters);
       
       // Version inputs update
       $versionRows.find('input').on('keyup change', updatePersonaPrompt);
       
       // Custom prompt changes
       $customPrompt.on('keyup change', updatePersonaPrompt);
   }
   
   function handlePersonaChange() {
       const selected = $personaSelect.val();
       
       // Hide all version rows
       $versionRows.hide();
       
       // Show/hide custom prompt
       if (selected === 'custom') {
           $customPersonaRow.show();
       } else {
           $customPersonaRow.hide();
           
           // Show relevant version input
           $versionRows.filter(`[data-persona-for="${selected}"]`).show();
       }
       
       updatePersonaPrompt();
   }
   
   function handleArvanToggle() {
       if ($enableArvan.is(':checked')) {
           $arvanRows.show();
       } else {
           $arvanRows.hide();
       }
   }
   
   function updatePersonaPrompt() {
       const selected = $personaSelect.val();
       
       if (selected === 'custom') {
           return; // Don't update custom prompt
       }
       
       let prompt = personas[selected] || personas.general;
       
       // Replace version placeholder
       const $versionInput = $versionRows.filter(`[data-persona-for="${selected}"]`).find('input');
       if ($versionInput.length && $versionInput.val()) {
           prompt = prompt.replace('{version}', `نسخه ${$versionInput.val()}`);
       }
       
       // Update preview if exists
       const $preview = $('#system-prompt-preview');
       if ($preview.length) {
           $preview.val(prompt);
       }
   }
   
   function handleClearMemory(e) {
       e.preventDefault();
       
       if (!confirm('آیا از پاک کردن حافظه مطمئن هستید؟ این عمل غیرقابل بازگشت است.')) {
           return;
       }
       
       const $btn = $(this);
       const $status = $('#clear-memory-status');
       
       // Show options dialog
       const clearType = prompt(
           'نوع پاکسازی را انتخاب کنید:\n' +
           '1. local - فقط حافظه محلی\n' +
           '2. embeddings - فقط embeddings و clusters\n' +
           '3. all - همه داده‌ها',
           'all'
       );
       
       if (!clearType) return;
       
       $btn.prop('disabled', true).html('<span class="adm-spinner"></span>در حال پاکسازی...');
       
       $.post(adm_admin_ajax.url, {
           action: 'adm_clear_memory',
           nonce: adm_admin_ajax.nonce,
           clear_type: clearType
       })
       .done(function(response) {
           if (response.success) {
               $status.text(response.data.message).addClass('status-success');
           } else {
               $status.text('خطا: ' + response.data.error).addClass('status-error');
           }
       })
       .fail(function() {
           $status.text('خطای سرور.').addClass('status-error');
       })
       .always(function() {
           $btn.prop('disabled', false).text('پاک کردن کامل حافظه');
           
           setTimeout(function() {
               $status.text('').removeClass('status-success status-error');
           }, 5000);
       });
   }
   
   function testArvanConnection(e) {
       e.preventDefault();
       
       const $btn = $(this);
       const $status = $('#arvan-test-status');
       
       $btn.prop('disabled', true).html('<span class="adm-spinner"></span>در حال تست...');
       
       $.post(adm_admin_ajax.url, {
           action: 'adm_test_connection',
           nonce: adm_admin_ajax.nonce
       })
       .done(function(response) {
           if (response.success) {
               $status.text(response.data.message).addClass('status-success');
           } else {
               $status.text(response.data.message).addClass('status-error');
           }
       })
       .fail(function() {
           $status.text('خطای سرور.').addClass('status-error');
       })
       .always(function() {
           $btn.prop('disabled', false).text('تست اتصال');
           
           setTimeout(function() {
               $status.text('').removeClass('status-success status-error');
           }, 5000);
       });
   }
   
   function updateClusters(e) {
       e.preventDefault();
       
       const $btn = $(this);
       const $status = $('#cluster-status');
       
       $btn.prop('disabled', true).html('<span class="adm-spinner"></span>در حال بروزرسانی...');
       
       $.post(adm_admin_ajax.url, {
           action: 'adm_update_clusters',
           nonce: adm_admin_ajax.nonce
       })
       .done(function(response) {
           if (response.success) {
               $status.text(response.data.message).addClass('status-success');
           } else {
               $status.text(response.data.message).addClass('status-error');
           }
       })
       .fail(function() {
           $status.text('خطای سرور.').addClass('status-error');
       })
       .always(function() {
           $btn.prop('disabled', false).text('بروزرسانی Clusters');
           
           setTimeout(function() {
               $status.text('').removeClass('status-success status-error');
           }, 5000);
       });
   }
   
   // Live search for memory table
   const $searchInput = $('#memory-search');
   if ($searchInput.length) {
       let searchTimer;
       
       $searchInput.on('keyup', function() {
           clearTimeout(searchTimer);
           const query = $(this).val();
           
           searchTimer = setTimeout(function() {
               loadMemoryTable(query);
           }, 500);
       });
   }
   
   function loadMemoryTable(search = '') {
       const $table = $('#memory-table-body');
       if (!$table.length) return;
       
       $table.html('<tr><td colspan="5" style="text-align: center;"><span class="adm-spinner"></span>در حال بارگذاری...</td></tr>');
       
       $.get(adm_admin_ajax.url, {
           action: 'adm_load_memories',
           nonce: adm_admin_ajax.nonce,
           search: search,
           page: 1
       })
       .done(function(response) {
           if (response.success) {
               $table.html(response.data.html);
           } else {
               $table.html('<tr><td colspan="5" style="text-align: center;">خطا در بارگذاری</td></tr>');
           }
       });
   }
   
   // Addon management
   $(document).on('click', '.addon-activate', function(e) {
       e.preventDefault();
       
       const $btn = $(this);
       const addonSlug = $btn.data('addon');
       
       $btn.prop('disabled', true).text('در حال فعال‌سازی...');
       
       $.post(adm_admin_ajax.url, {
           action: 'adm_toggle_addon',
           nonce: adm_admin_ajax.nonce,
           addon: addonSlug,
           activate: true
       })
       .done(function(response) {
           if (response.success) {
               $btn.text('فعال').prop('disabled', true);
               location.reload(); // Reload to apply addon changes
           } else {
               alert('خطا در فعال‌سازی افزونه');
               $btn.text('فعال‌سازی').prop('disabled', false);
           }
       });
   });
   
   // System info copy button
   $('#copy-system-info').on('click', function() {
       const info = $('#system-info-content').text();
       
       const $temp = $('<textarea>');
       $('body').append($temp);
       $temp.val(info).select();
       document.execCommand('copy');
       $temp.remove();
       
       $(this).text('کپی شد!');
       setTimeout(() => {
           $(this).text('کپی اطلاعات');
       }, 2000);
   });
});
