<?php
/**
 * Settings Manager Class
 * Handles System Settings
 */

require_once __DIR__ . '/../config/database.php';

class SettingsManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get a single setting value
     */
    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    }

    /**
     * Get multiple settings by prefix
     */
    public function getSettingsByPrefix($prefix) {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE ?");
        $stmt->execute([$prefix . '%']);
        $results = $stmt->fetchAll();
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Get all display settings
     */
    public function getDisplaySettings() {
        return $this->getSettingsByPrefix('display_');
    }

    /**
     * Update a setting
     */
    public function updateSetting($key, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        return $stmt->execute([$key, $value, $value]);
    }

    /**
     * Update multiple settings
     */
    public function updateSettings($settings) {
        foreach ($settings as $key => $value) {
            $this->updateSetting($key, $value);
        }
        return true;
    }

    /**
     * Get all settings with descriptions
     */
    public function getAllSettings() {
        $stmt = $this->db->query("SELECT * FROM system_settings ORDER BY setting_key");
        return $stmt->fetchAll();
    }

    /**
     * Delete a setting
     */
    public function deleteSetting($key) {
        $stmt = $this->db->prepare("DELETE FROM system_settings WHERE setting_key = ?");
        return $stmt->execute([$key]);
    }
}
