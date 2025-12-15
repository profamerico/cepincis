<?php
class ProjectManager {
    private $projects_file;
    
    public function __construct() {
        $this->projects_file = __DIR__ . '/../data/projects.json';
        $this->ensureFileExists();
    }
    
    private function ensureFileExists() {
        if (!file_exists(dirname($this->projects_file))) {
            mkdir(dirname($this->projects_file), 0777, true);
        }
        if (!file_exists($this->projects_file)) {
            file_put_contents($this->projects_file, json_encode([]));
        }
    }
    
    public function createProject($user_id, $title, $description, $category = 'Geral') {
        $projects = $this->getProjects();
        
        $new_project = [
            'id' => uniqid(),
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $projects[] = $new_project;
        
        if (file_put_contents($this->projects_file, json_encode($projects, JSON_PRETTY_PRINT))) {
            return $new_project;
        }
        
        return false;
    }
    
    public function getUserProjects($user_id) {
        $projects = $this->getProjects();
        $user_projects = [];
        
        foreach ($projects as $project) {
            if ($project['user_id'] === $user_id) {
                $user_projects[] = $project;
            }
        }
        
        return $user_projects;
    }
    
    public function getProject($project_id) {
        $projects = $this->getProjects();
        
        foreach ($projects as $project) {
            if ($project['id'] === $project_id) {
                return $project;
            }
        }
        
        return false;
    }
    
    public function updateProject($project_id, $data) {
        $projects = $this->getProjects();
        
        foreach ($projects as &$project) {
            if ($project['id'] === $project_id) {
                $project['title'] = $data['title'] ?? $project['title'];
                $project['description'] = $data['description'] ?? $project['description'];
                $project['category'] = $data['category'] ?? $project['category'];
                $project['status'] = $data['status'] ?? $project['status'];
                $project['updated_at'] = date('Y-m-d H:i:s');
                
                if (file_put_contents($this->projects_file, json_encode($projects, JSON_PRETTY_PRINT))) {
                    return $project;
                }
            }
        }
        
        return false;
    }
    
    public function deleteProject($project_id) {
        $projects = $this->getProjects();
        $new_projects = [];
        
        foreach ($projects as $project) {
            if ($project['id'] !== $project_id) {
                $new_projects[] = $project;
            }
        }
        
        return file_put_contents($this->projects_file, json_encode($new_projects, JSON_PRETTY_PRINT));
    }
    
    public function getUserStats($user_id) {
        $projects = $this->getUserProjects($user_id);
        
        $stats = [
            'total' => count($projects),
            'active' => 0,
            'completed' => 0,
            'pending' => 0
        ];
        
        foreach ($projects as $project) {
            if ($project['status'] === 'active') $stats['active']++;
            if ($project['status'] === 'completed') $stats['completed']++;
            if ($project['status'] === 'pending') $stats['pending']++;
        }
        
        return $stats;
    }
    
    private function getProjects() {
        if (!file_exists($this->projects_file)) {
            return [];
        }
        
        $content = file_get_contents($this->projects_file);
        $projects = json_decode($content, true);
        
        return is_array($projects) ? $projects : [];
    }
}
?>