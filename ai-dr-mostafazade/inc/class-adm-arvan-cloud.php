<?php
/**
 * ArvanCloud Database Integration
 */
class ADM_ArvanCloud {
    
    private $config;
    private $db_connection = null;
    private $redis_connection = null;
    
    public function __construct() {
        $options = get_option('adm_options', []);
        $this->config = $options['arvan_config'] ?? [];
    }
    
    /**
     * Connect to ArvanCloud MySQL/PostgreSQL
     */
    public function connect_database() {
        if (!empty($this->config['database']['type']) && !empty($this->config['database']['host'])) {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $this->config['database']['type'],
                $this->config['database']['host'],
                $this->config['database']['port'] ?? 3306,
                $this->config['database']['name']
            );
            
            try {
                $this->db_connection = new PDO(
                    $dsn,
                    $this->decrypt($this->config['database']['user']),
                    $this->decrypt($this->config['database']['pass']),
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                return true;
            } catch (PDOException $e) {
                error_log('ArvanCloud DB Connection Error: ' . $e->getMessage());
                return false;
            }
        }
        return false;
    }
    
    /**
     * Connect to ArvanCloud Redis
     */
    public function connect_redis() {
        if (!class_exists('Redis')) {
            return false;
        }
        
        if (!empty($this->config['redis']['host'])) {
            try {
                $this->redis_connection = new Redis();
                $this->redis_connection->connect(
                    $this->config['redis']['host'],
                    $this->config['redis']['port'] ?? 6379
                );
                
                if (!empty($this->config['redis']['auth'])) {
                    $this->redis_connection->auth($this->decrypt($this->config['redis']['auth']));
                }
                
                return true;
            } catch (Exception $e) {
                error_log('ArvanCloud Redis Connection Error: ' . $e->getMessage());
                return false;
            }
        }
        return false;
    }
    
    /**
     * Sync memory to ArvanCloud
     */
    public function sync_memory($memory_id, $data) {
        if (!$this->connect_database()) {
            return false;
        }
        
        try {
            // Insert into remote database
            $stmt = $this->db_connection->prepare(
                "INSERT INTO semantic_memory (local_id, user_query, ai_response, embedding, created_at) 
                 VALUES (:local_id, :user_query, :ai_response, :embedding, :created_at)
                 ON DUPLICATE KEY UPDATE 
                 user_query = VALUES(user_query),
                 ai_response = VALUES(ai_response),
                 embedding = VALUES(embedding)"
            );
            
            $stmt->execute([
                ':local_id' => $memory_id,
                ':user_query' => $data['user_query'],
                ':ai_response' => $data['ai_response'],
                ':embedding' => $data['embedding'] ?? null,
                ':created_at' => current_time('mysql')
            ]);
            
            $remote_id = $this->db_connection->lastInsertId();
            
            // Update sync status
            global $wpdb;
            $wpdb->replace(
                $wpdb->prefix . 'adm_arvan_sync',
                [
                    'local_id' => $memory_id,
                    'remote_id' => $remote_id,
                    'sync_status' => 'synced',
                    'last_sync' => current_time('mysql')
                ]
            );
            
            // Cache in Redis if available
            if ($this->redis_connection) {
                $this->cache_embedding($memory_id, $data['embedding']);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('ArvanCloud Sync Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cache embedding in Redis
     */
    private function cache_embedding($memory_id, $embedding) {
        if ($this->redis_connection && $embedding) {
            $key = 'adm:embedding:' . $memory_id;
            $this->redis_connection->set($key, json_encode($embedding));
            $this->redis_connection->expire($key, 86400); // 24 hours
        }
    }
    
    /**
     * Get cached embedding from Redis
     */
    public function get_cached_embedding($memory_id) {
        if ($this->connect_redis()) {
            $key = 'adm:embedding:' . $memory_id;
            $data = $this->redis_connection->get($key);
            if ($data) {
                return json_decode($data, true);
            }
        }
        return false;
    }
    
    /**
     * Search in ArvanCloud database
     */
    public function search_remote($query, $limit = 10) {
        if (!$this->connect_database()) {
            return [];
        }
        
        try {
            $stmt = $this->db_connection->prepare(
                "SELECT * FROM semantic_memory 
                 WHERE MATCH(user_query, ai_response) AGAINST(:query IN NATURAL LANGUAGE MODE)
                 ORDER BY MATCH(user_query, ai_response) AGAINST(:query) DESC
                 LIMIT :limit"
            );
            
            $stmt->bindValue(':query', $query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('ArvanCloud Search Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Encrypt sensitive data
     * Made public to be accessible from settings
     */
    public function encrypt($data) {
        if (empty($data)) {
            return $data;
        }
        $key = wp_salt('auth');
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
    }
    
    /**
     * Decrypt sensitive data
     * Made public to be accessible from settings
     */
    public function decrypt($data) {
        if (empty($data)) {
            return $data;
        }
        $key = wp_salt('auth');
        return openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    }
}
