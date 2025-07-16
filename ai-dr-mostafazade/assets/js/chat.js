/**
* AI Dr. Mostafazade - Enhanced Chat Interface
*/
jQuery(document).ready(function($) {
   'use strict';
   
   // Elements
   const $form = $('#adm-chat-form');
   const $input = $('#adm-user-input');
   const $messages = $('#adm-chat-messages');
   const $sendBtn = $('.adm-send-btn');
   const $status = $('#adm-status-text');
   const $formatScript = $('#adm-format-script');
   const $useEmbeddings = $('#adm-use-embeddings');
   const $voiceBtn = $('.adm-voice-btn');
   
   // State
   let isProcessing = false;
   let currentMemoryId = null;
   let contextFiles = [];
   let codeEditors = new Map();
   
   // Initialize
   init();
   
   function init() {
       // Make body fullscreen if needed
       if ($('#adm-chat-container').height() === window.innerHeight) {
           $('body').addClass('adm-fullscreen-chat');
       }
       
       // Load memory stats
       loadMemoryStats();
       
       // Setup event handlers
       setupEventHandlers();
       
       // Initialize CodeMirror for existing code blocks
       initializeCodeEditors();
       
       // Setup context file handling
       setupContextHandling();
   }
   
   function setupEventHandlers() {
       // Form submission
       $form.on('submit', handleSubmit);
       
       // Enter key handling
       $input.on('keydown', function(e) {
           if (e.key === 'Enter' && !e.shiftKey) {
               e.preventDefault();
               $form.submit();
           }
       });
       
       // Voice input
       $voiceBtn.on('click', toggleVoiceRecording);
       
       // Code action buttons
       $(document).on('click', '.adm-copy-btn', handleCopyCode);
       $(document).on('click', '.adm-run-btn', handleRunCode);
       $(document).on('click', '.adm-save-btn', handleSaveCode);
       
       // Feedback buttons
       $(document).on('click', '.adm-feedback-btn', handleFeedback);
       
       // Tab handling
       $('.adm-new-tab').on('click', createNewTab);
       $(document).on('click', '.adm-tab', switchTab);
   }
   
   function handleSubmit(e) {
       e.preventDefault();
       
       if (isProcessing) return;
       
       const message = $input.val().trim();
       if (!message) return;
       
       // Add user message
       addMessage(message, 'user');
       
       // Clear input and disable
       $input.val('').prop('disabled', true);
       $sendBtn.prop('disabled', true);
       isProcessing = true;
       
       // Show typing indicator
       const typingId = showTypingIndicator();
       
       // Prepare data
       const data = {
           action: 'adm_send_chat',
           nonce: adm_ajax.nonce,
           message: message,
           use_embeddings: $useEmbeddings.is(':checked'),
           context_files: contextFiles.map(f => ({
               name: f.name,
               content: f.content
           }))
       };
       
       // Send request
       $.ajax({
           url: adm_ajax.url,
           type: 'POST',
           data: data,
           success: function(response) {
               removeTypingIndicator(typingId);
               
               if (response.success) {
                   let content = response.data.content[0].text;
                   currentMemoryId = response.data.memory_id;
                   
                   // Format as script if requested
                   if ($formatScript.is(':checked')) {
                       content = formatAsScript(content);
                   }
                   
                   // Add bot message
                   addMessage(content, 'bot', currentMemoryId);
               } else {
                   addMessage('خطا: ' + (response.error || 'خطای ناشناخته'), 'error');
               }
           },
           error: function() {
               removeTypingIndicator(typingId);
               addMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
           },
           complete: function() {
               $input.prop('disabled', false).focus();
               $sendBtn.prop('disabled', false);
               isProcessing = false;
               $status.text('');
           }
       });
   }
   
   function addMessage(content, type, memoryId) {
       const $message = $('<div class="adm-message"></div>')
           .addClass('adm-message-' + type)
           .attr('data-memory-id', memoryId);
       
       // Header
       const header = `
           <div class="adm-message-header">
               <span class="adm-avatar">${type === 'bot' ? '🤖' : '👤'}</span>
               <span class="adm-name">${type === 'bot' ? 'AI Dr. Mostafazade' : 'شما'}</span>
           </div>
       `;
       
       // Process content for code blocks
       const processedContent = processCodeBlocks(content);
       
       // Message content
       const messageContent = `
           <div class="adm-message-content">
               ${processedContent}
           </div>
       `;
       
       // Feedback buttons (for bot messages)
       let feedback = '';
       if (type === 'bot' && memoryId) {
           feedback = `
               <div class="adm-feedback">
                   <button class="adm-feedback-btn" data-rating="5" title="عالی">👍</button>
                   <button class="adm-feedback-btn" data-rating="3" title="متوسط">👌</button>
                   <button class="adm-feedback-btn" data-rating="1" title="ضعیف">👎</button>
               </div>
           `;
       }
       
       $message.html(header + messageContent + feedback);
       $messages.append($message);
       
       // Initialize CodeMirror for new code blocks
       $message.find('.adm-code-content').each(function() {
           initializeCodeEditor(this);
       });
       
       // Scroll to bottom
       $messages.scrollTop($messages[0].scrollHeight);
   }
   
   function processCodeBlocks(content) {
       // Replace code blocks with enhanced structure
       return content.replace(/```(\w*)\n([\s\S]+?)```/g, function(match, lang, code) {
           const uniqueId = 'code-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
           
           return `
               <div class="adm-code-block" data-lang="${lang || 'plaintext'}">
                   <div class="adm-code-header">
                       <span class="adm-code-lang">${lang || 'کد'}</span>
                       <div class="adm-code-actions">
                           <button class="adm-code-btn adm-copy-btn" data-target="${uniqueId}">کپی</button>
                           ${lang === 'bash' || lang === 'sh' ? '<button class="adm-code-btn adm-run-btn" data-target="' + uniqueId + '">اجرا</button>' : ''}
                           <button class="adm-code-btn adm-save-btn" data-target="${uniqueId}">ذخیره</button>
                       </div>
                   </div>
                   <div class="adm-code-content" id="${uniqueId}" data-code="${encodeHtml(code)}"></div>
               </div>
           `;
       }).replace(/\n/g, '<br>');
   }
   
   function initializeCodeEditor(element) {
       const $element = $(element);
       const code = $element.attr('data-code');
       const lang = $element.closest('.adm-code-block').attr('data-lang');
       
       if (!code) return;
       
       // Decode the code
       const decodedCode = decodeHtml(code);
       
       // Create CodeMirror instance
       const editor = CodeMirror(element, {
           value: decodedCode,
           mode: getCodeMirrorMode(lang),
           theme: 'dracula',
           lineNumbers: true,
           readOnly: true,
           lineWrapping: true,
           viewportMargin: Infinity,
           extraKeys: {
               "Ctrl-C": function(cm) {
                   copyToClipboard(cm.getValue());
               }
           }
       });
       
       // Store editor reference
       codeEditors.set($element.attr('id'), editor);
       
       // Auto-resize
       setTimeout(() => editor.refresh(), 100);
   }
   
   function getCodeMirrorMode(lang) {
       const modeMap = {
           'javascript': 'javascript',
           'js': 'javascript',
           'python': 'python',
           'py': 'python',
           'php': 'php',
           'bash': 'shell',
           'sh': 'shell',
           'sql': 'sql',
           'html': 'htmlmixed',
           'css': 'css',
           'json': 'javascript',
           'xml': 'xml'
       };
       
       return modeMap[lang] || 'plaintext';
   }
   
   function handleCopyCode(e) {
       const targetId = $(this).data('target');
       const editor = codeEditors.get(targetId);
       
       if (editor) {
           const code = editor.getValue();
           copyToClipboard(code);
           
           // Visual feedback
           $(this).text('کپی شد!').addClass('copied');
           setTimeout(() => {
               $(this).text('کپی').removeClass('copied');
           }, 2000);
       }
   }
   
   function copyToClipboard(text) {
       // Modern approach
       if (navigator.clipboard && navigator.clipboard.writeText) {
           navigator.clipboard.writeText(text);
       } else {
           // Fallback
           const $temp = $('<textarea>');
           $('body').append($temp);
           $temp.val(text).select();
           document.execCommand('copy');
           $temp.remove();
       }
   }
   
   function handleRunCode(e) {
       const targetId = $(this).data('target');
       const editor = codeEditors.get(targetId);
       
       if (editor) {
           const code = editor.getValue();
           
           // Send code for execution (to addon if available)
           if (window.cphAddons && window.cphAddons.terminal) {
               window.cphAddons.terminal.execute(code);
           } else {
               alert('ترمینال در دسترس نیست. لطفاً افزونه ترمینال را نصب کنید.');
           }
       }
   }
   
   function handleSaveCode(e) {
       const targetId = $(this).data('target');
       const editor = codeEditors.get(targetId);
       
       if (editor) {
           const code = editor.getValue();
           const lang = $(this).closest('.adm-code-block').data('lang');
           const filename = prompt('نام فایل را وارد کنید:', `script.${lang || 'txt'}`);
           
           if (filename) {
               downloadFile(filename, code);
           }
       }
   }
   
   function downloadFile(filename, content) {
       const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
       const url = URL.createObjectURL(blob);
       const a = document.createElement('a');
       a.href = url;
       a.download = filename;
       a.click();
       URL.revokeObjectURL(url);
   }
   
   function handleFeedback(e) {
       const $btn = $(this);
       const rating = $btn.data('rating');
       const memoryId = $btn.closest('.adm-message').data('memory-id');
       
       if (!memoryId) return;
       
       // Visual feedback
       $btn.siblings().removeClass('active');
       $btn.addClass('active');
       
       // Send feedback
       $.post(adm_ajax.url, {
           action: 'adm_rate_response',
           nonce: adm_ajax.nonce,
           memory_id: memoryId,
           rating: rating,
           helpful: rating >= 3
       });
   }
   
   function formatAsScript(content) {
       const codeBlocks = [];
       const regex = /```(\w*)\n([\s\S]+?)```/g;
       let match;
       
       while ((match = regex.exec(content)) !== null) {
           const lang = match[1].toLowerCase();
           const code = match[2];
           
           let shebang = '#!/bin/bash';
           if (lang.includes('python')) shebang = '#!/usr/bin/env python3';
           else if (lang.includes('php')) shebang = '<?php';
           else if (lang.includes('node') || lang.includes('js')) shebang = '#!/usr/bin/env node';
           
           if (codeBlocks.length === 0) {
               codeBlocks.push(shebang + '\n\n# Generated by AI Dr. Mostafazade\n# ' + new Date().toLocaleString('fa-IR') + '\n\n');
           }
           
           codeBlocks.push(code);
       }
       
       if (codeBlocks.length > 0) {
           return '```bash\n' + codeBlocks.join('\n\n') + '\n```';
       }
       
       return content;
   }
   
   function showTypingIndicator() {
       const id = 'typing-' + Date.now();
       const indicator = `
           <div class="adm-message adm-message-bot" id="${id}">
               <div class="adm-message-header">
                   <span class="adm-avatar">🤖</span>
                   <span class="adm-name">AI Dr. Mostafazade</span>
               </div>
               <div class="adm-message-content">
                   <div class="adm-loading">
                       <span></span>
                       <span></span>
                       <span></span>
                   </div>
               </div>
           </div>
       `;
       
       $messages.append(indicator);
       $messages.scrollTop($messages[0].scrollHeight);
       
       return id;
   }
   
   function removeTypingIndicator(id) {
       $('#' + id).remove();
   }
   
   // Context file handling
   function setupContextHandling() {
       const $dropzone = $('.adm-context-dropzone');
       const $fileInput = $('#adm-context-upload');
       const $filesList = $('.adm-context-files');
       
       // Click to upload
       $dropzone.on('click', () => $fileInput.click());
       
       // File input change
       $fileInput.on('change', function(e) {
           handleFiles(e.target.files);
       });
       
       // Drag and drop
       $dropzone.on('dragover', function(e) {
           e.preventDefault();
           $(this).addClass('drag-over');
       });
       
       $dropzone.on('dragleave', function() {
           $(this).removeClass('drag-over');
       });
       
       $dropzone.on('drop', function(e) {
           e.preventDefault();
           $(this).removeClass('drag-over');
           handleFiles(e.originalEvent.dataTransfer.files);
       });
   }
   
   function handleFiles(files) {
       Array.from(files).forEach(file => {
           if (file.size > 5 * 1024 * 1024) { // 5MB limit
               alert('فایل ' + file.name + ' بیش از حد بزرگ است (حداکثر 5MB)');
               return;
           }
           
           const reader = new FileReader();
           reader.onload = function(e) {
               const fileData = {
                   name: file.name,
                   type: file.type,
                   size: file.size,
                   content: e.target.result
               };
               
               contextFiles.push(fileData);
               displayContextFile(fileData);
           };
           
           reader.readAsText(file);
       });
   }
   
   function displayContextFile(file) {
       const $file = $(`
           <div class="adm-context-file" data-name="${file.name}">
               <span class="file-icon">📄</span>
               <span class="file-name">${file.name}</span>
               <button class="remove-file" title="حذف">×</button>
           </div>
       `);
       
       $file.find('.remove-file').on('click', function() {
           contextFiles = contextFiles.filter(f => f.name !== file.name);
           $file.remove();
       });
       
       $('.adm-context-files').append($file);
   }
   
   // Voice recording
   let mediaRecorder = null;
   let audioChunks = [];
   
   function toggleVoiceRecording() {
       if (mediaRecorder && mediaRecorder.state === 'recording') {
           stopRecording();
       } else {
           startRecording();
       }
   }
   
   function startRecording() {
       navigator.mediaDevices.getUserMedia({ audio: true })
           .then(stream => {
               mediaRecorder = new MediaRecorder(stream);
               audioChunks = [];
               
               mediaRecorder.ondataavailable = event => {
                   audioChunks.push(event.data);
               };
               
               mediaRecorder.onstop = () => {
                   const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                   // Here you would send to speech-to-text API
                   // For now, just indicate recording finished
                   $voiceBtn.removeClass('recording');
               };
               
               mediaRecorder.start();
               $voiceBtn.addClass('recording');
           })
           .catch(err => {
               alert('دسترسی به میکروفون امکان‌پذیر نیست.');
               console.error(err);
           });
   }
   
   function stopRecording() {
       if (mediaRecorder) {
           mediaRecorder.stop();
           mediaRecorder.stream.getTracks().forEach(track => track.stop());
       }
   }
   
   // Tab management
   let tabCounter = 1;
   
   function createNewTab() {
       const tabId = 'tab-' + (++tabCounter);
       const $tab = $(`<div class="adm-tab" data-tab="${tabId}">گفتگو ${tabCounter}</div>`);
       
       $('.adm-new-tab').before($tab);
       
       // Create new chat container (would need to implement multiple chat states)
       // For now, just switch to the new tab
       switchToTab(tabId);
   }
   
   function switchTab(e) {
       const tabId = $(this).data('tab');
       switchToTab(tabId);
   }
   
   function switchToTab(tabId) {
       $('.adm-tab').removeClass('active');
       $(`.adm-tab[data-tab="${tabId}"]`).addClass('active');
       
       // Would load the corresponding chat history here
   }
   
   // Load memory statistics
   function loadMemoryStats() {
       $.get(adm_ajax.url, {
           action: 'adm_get_stats',
           nonce: adm_ajax.nonce
       }, function(response) {
           if (response.success) {
               $('#total-memories').text(response.data.memories);
               $('#total-embeddings').text(response.data.embeddings);
               $('#total-clusters').text(response.data.clusters);
           }
       });
   }
   
   // Utility functions
   function encodeHtml(str) {
       return str.replace(/[&<>"']/g, function(match) {
           const map = {
               '&': '&amp;',
               '<': '&lt;',
               '>': '&gt;',
               '"': '&quot;',
               "'": '&#39;'
           };
           return map[match];
       });
   }
   
   function decodeHtml(str) {
       const txt = document.createElement('textarea');
       txt.innerHTML = str;
       return txt.value;
   }
   
   // Initialize CodeMirror modes
   function loadCodeMirrorModes() {
       const modes = ['javascript', 'python', 'php', 'shell', 'sql', 'htmlmixed', 'css', 'xml'];
       
       modes.forEach(mode => {
           const script = document.createElement('script');
           script.src = adm_ajax.plugin_url + 'assets/lib/codemirror/mode/' + mode + '/' + mode + '.js';
           document.head.appendChild(script);
       });
   }
   
   loadCodeMirrorModes();
});
