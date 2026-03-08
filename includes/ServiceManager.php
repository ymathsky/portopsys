<?php
/**
 * Service Category Management Class
 */

require_once __DIR__ . '/../config/config.php';

class ServiceManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all active service categories
     */
    public function getActiveCategories() {
        $stmt = $this->db->query("
            SELECT * FROM service_categories 
            WHERE is_active = 1 
            ORDER BY priority_level DESC, name ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get service category by ID
     */
    public function getCategoryById($id) {
        $stmt = $this->db->prepare("SELECT * FROM service_categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all service counters
     */
    public function getCounters($activeOnly = false) {
        $sql = "SELECT * FROM service_counters";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY counter_number ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get counter by ID
     */
    public function getCounterById($id) {
        $stmt = $this->db->prepare("SELECT * FROM service_counters WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get counter status with current token info
     */
    public function getCounterStatus() {
        $stmt = $this->db->query("SELECT * FROM counter_status_view ORDER BY counter_number ASC");
        $counters = $stmt->fetchAll();

        // Add called_tokens_list: all tokens in 'called' state per counter (for mass call display)
        $stmt2 = $this->db->query("
            SELECT counter_id,
                   GROUP_CONCAT(token_number ORDER BY called_at ASC SEPARATOR ',') AS called_tokens_list
            FROM tokens
            WHERE status = 'called'
            GROUP BY counter_id
        ");
        $calledMap = [];
        while ($row = $stmt2->fetch()) {
            $calledMap[$row['counter_id']] = $row['called_tokens_list'];
        }
        foreach ($counters as &$counter) {
            $counter['called_tokens_list'] = $calledMap[$counter['id']] ?? '';
        }
        return $counters;
    }
    
    /**
     * Update counter status
     */
    public function updateCounterStatus($counterId, $status, $staffName = null) {
        $stmt = $this->db->prepare("
            UPDATE service_counters 
            SET current_status = ?, staff_name = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $staffName, $counterId]);
    }
    
    /**
     * Get services for a counter
     */
    public function getCounterServices($counterId) {
        $stmt = $this->db->prepare("
            SELECT sc.* 
            FROM service_categories sc
            INNER JOIN counter_services cs ON sc.id = cs.service_category_id
            WHERE cs.counter_id = ? AND sc.is_active = 1
        ");
        $stmt->execute([$counterId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all service categories (including inactive)
     */
    public function getAllCategories() {
        $stmt = $this->db->query("
            SELECT * FROM service_categories 
            ORDER BY priority_level DESC, name ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Add a new service category
     */
    public function addCategory($name, $code, $priorityLevel, $avgServiceTime, $description = null, $isActive = true) {
        $stmt = $this->db->prepare("
            INSERT INTO service_categories (name, code, priority_level, avg_service_time, description, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$name, $code, $priorityLevel, $avgServiceTime, $description, $isActive ? 1 : 0]);
    }
    
    /**
     * Update a service category
     */
    public function updateCategory($id, $name, $code, $priorityLevel, $avgServiceTime, $description = null, $isActive = true) {
        $stmt = $this->db->prepare("
            UPDATE service_categories 
            SET name = ?, code = ?, priority_level = ?, avg_service_time = ?, description = ?, is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([$name, $code, $priorityLevel, $avgServiceTime, $description, $isActive ? 1 : 0, $id]);
    }
    
    /**
     * Delete a service category
     */
    public function deleteCategory($id) {
        $stmt = $this->db->prepare("DELETE FROM service_categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Toggle service category status
     */
    public function toggleCategoryStatus($id) {
        $stmt = $this->db->prepare("UPDATE service_categories SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Check if service category is in use
     */
    public function isCategoryInUse($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM tokens WHERE service_category_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Get service category usage count
     */
    public function getCategoryUsageCount($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM tokens WHERE service_category_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
