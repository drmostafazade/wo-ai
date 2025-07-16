<?php
// Add this to render_memory_page method in class-adm-settings.php

public function render_memory_page() {
    global $wpdb;
    
    // Get memories
    $memories = $wpdb->get_results(
        "SELECT m.*, 
                (SELECT COUNT(*) FROM {$wpdb->prefix}adm_feedback f WHERE f.memory_id = m.id) as feedback_count,
                (SELECT AVG(rating) FROM {$wpdb->prefix}adm_feedback f WHERE f.memory_id = m.id) as avg_rating
         FROM {$wpdb->prefix}adm_memory m 
         ORDER BY m.id DESC 
         LIMIT 50"
    );
    ?>
    <div class="wrap">
        <h1>مدیریت حافظه معنایی</h1>
        
        <div class="adm-memory-stats" style="background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
            <h3>آمار کلی</h3>
            <?php
            $total_memories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_memory");
            $total_embeddings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_embeddings");
            $total_clusters = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_clusters");
            $total_feedback = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adm_feedback");
            ?>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div style="text-align: center;">
                    <h4><?php echo number_format($total_memories); ?></h4>
                    <p>کل خاطرات</p>
                </div>
                <div style="text-align: center;">
                    <h4><?php echo number_format($total_embeddings); ?></h4>
                    <p>Embeddings</p>
                </div>
                <div style="text-align: center;">
                    <h4><?php echo number_format($total_clusters); ?></h4>
                    <p>Clusters</p>
                </div>
                <div style="text-align: center;">
                    <h4><?php echo number_format($total_feedback); ?></h4>
                    <p>بازخوردها</p>
                </div>
            </div>
        </div>
        
        <div class="adm-memory-table" style="background: #fff; padding: 20px; border-radius: 8px;">
            <h3>خاطرات اخیر</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>سوال کاربر</th>
                        <th>پاسخ AI</th>
                        <th>امتیاز</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($memories as $memory): ?>
                    <tr>
                        <td><?php echo $memory->id; ?></td>
                        <td><?php echo esc_html(substr($memory->user_query, 0, 100)) . '...'; ?></td>
                        <td><?php echo esc_html(substr($memory->ai_response, 0, 100)) . '...'; ?></td>
                        <td>
                            <?php if ($memory->avg_rating): ?>
                                ⭐ <?php echo number_format($memory->avg_rating, 1); ?>/5
                                (<?php echo $memory->feedback_count; ?>)
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo date_i18n('Y/m/d H:i', strtotime($memory->created_at)); ?></td>
                        <td>
                            <button class="button view-memory" data-id="<?php echo $memory->id; ?>">مشاهده</button>
                            <button class="button delete-memory" data-id="<?php echo $memory->id; ?>">حذف</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <style>
    .adm-memory-stats h4 {
        font-size: 28px;
        margin: 0;
        color: #007cba;
    }
    </style>
    <?php
}
