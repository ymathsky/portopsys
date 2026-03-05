<?php
/**
 * Install Display Settings
 * Run this once to add display board color settings to database
 */

require_once __DIR__ . '/config/config.php';

$db = Database::getInstance()->getConnection();

$settings = [
    ['display_bg_gradient_start', '#667eea', 'Display board background gradient start color'],
    ['display_bg_gradient_end', '#764ba2', 'Display board background gradient end color'],
    ['display_accent_color', '#f093fb', 'Display board accent color'],
    ['display_card_bg', 'rgba(255, 255, 255, 0.95)', 'Display board card background'],
    ['display_success_gradient_start', '#11998e', 'Success status gradient start color'],
    ['display_success_gradient_end', '#38ef7d', 'Success status gradient end color'],
    ['display_alert_gradient_start', '#f093fb', 'Alert status gradient start color'],
    ['display_alert_gradient_end', '#f5576c', 'Alert status gradient end color'],
];

try {
    $stmt = $db->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    
    echo "✅ Display settings installed successfully!<br>";
    echo "You can now access the Display Settings page in the admin panel.<br><br>";
    echo "<a href='admin/settings.php'>Go to Display Settings</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
