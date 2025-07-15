<?php
class ProjectPreferences {
    private $projectId;
    private $table;
    
    public function __construct($projectId = 0) {
        global $wpdb;
        $this->projectId = $projectId;
        $this->table = $wpdb->prefix . 'claude_chat_projects';
    }
    
    public function getPreferences() {
        global $wpdb;
        
        if (!$this->projectId) {
            return $this->getDefaultPreferences();
        }
        
        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT preferences FROM {$this->table} WHERE id = %d",
            $this->projectId
        ));
        
        if ($project && $project->preferences) {
            return json_decode($project->preferences, true);
        }
        
        return $this->getDefaultPreferences();
    }
    
    public function savePreferences($preferences) {
        global $wpdb;
        
        $data = [
            'preferences' => json_encode($preferences),
            'updated_at' => current_time('mysql')
        ];
        
        if ($this->projectId) {
            return $wpdb->update($this->table, $data, ['id' => $this->projectId]);
        }
        
        return false;
    }
    
    public function createProject($name, $preferences = []) {
        global $wpdb;
        
        $wpdb->insert($this->table, [
            'name' => $name,
            'preferences' => json_encode($preferences),
            'created_at' => current_time('mysql')
        ]);
        
        return $wpdb->insert_id;
    }
    
    public function getAllProjects() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY created_at DESC");
    }
    
    private function getDefaultPreferences() {
        return [
            'language' => 'Persian',
            'response_format' => 'script',
            'tech_stack' => [],
            'project_context' => '',
            'code_style' => 'clean',
            'comment_language' => 'persian'
        ];
    }
}
